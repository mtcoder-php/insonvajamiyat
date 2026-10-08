<?php

namespace App\Services\Auth;

use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Enums\SocialProvider;
use App\Models\AuthorProfile;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\OAuth\OAuthException;
use App\Services\Auth\OAuth\OAuthUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Google / ORCID orqali kirish: akkauntni topish, bog'lash, uzish va yangi muallifni ro'yxatdan o'tkazish.
 *
 * Xavfsizlik qoidalari:
 *  - mavjud hisobga avtomatik bog'lash FAQAT provayder tasdiqlagan email mos kelganda;
 *  - bitta tashqi akkaunt faqat bitta foydalanuvchiga bog'lanadi;
 *  - parolsiz foydalanuvchi yagona kirish usulini uza olmaydi;
 *  - bloklangan / o'chirilgan hisob bu yo'l bilan ham kira olmaydi;
 *  - provayder tokenlari saqlanmaydi (faqat iD va email).
 */
class SocialAuthService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Kirish: bog'langan akkaunt yoki tasdiqlangan email bo'yicha foydalanuvchi.
     * Topilmasa — null (yangi foydalanuvchi ro'yxatdan o'tishni yakunlaydi).
     *
     * @throws OAuthException
     */
    public function findUserForLogin(SocialProvider $provider, OAuthUser $oauth): ?User
    {
        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $oauth->id)
            ->first();

        if ($account !== null) {
            $user = User::withTrashed()->find($account->user_id);
            $this->ensureCanSignIn($user);

            if ($oauth->email !== null && $account->email !== $oauth->email) {
                $account->forceFill(['email' => $oauth->email])->save();
            }

            return $user;
        }

        if ($oauth->email === null || ! $oauth->emailVerified) {
            return null;
        }

        $user = User::withTrashed()->where('email', $oauth->email)->first();

        if ($user === null) {
            return null;
        }

        $this->ensureCanSignIn($user);

        // Xodim hisoblari email bo'yicha avtomatik bog'lanmaydi: parol bilan kirib,
        // «Sozlamalar → Xavfsizlik» bo'limida bog'lanadi
        if ($user->isStaff()) {
            throw OAuthException::translated('Xodim hisobiga :provider ni avtomatik bog\'lab bo\'lmaydi. Parol bilan kiring va «Sozlamalar → Xavfsizlik» bo\'limida bog\'lang.', [
                'provider' => $provider->label(),
            ]);
        }

        if ($user->email_verified_at === null) {
            $this->claimUnverifiedAccount($user);
        }

        $this->link($user, $provider, $oauth);

        return $user;
    }

    /**
     * Tashqi akkauntni foydalanuvchiga bog'laydi (Sozlamalar → Xavfsizlik yoki email bo'yicha).
     *
     * @throws OAuthException
     */
    public function link(User $user, SocialProvider $provider, OAuthUser $oauth): SocialAccount
    {
        $existing = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $oauth->id)
            ->first();

        if ($existing !== null && $existing->user_id !== $user->id) {
            throw OAuthException::translated('Bu :provider akkaunti boshqa foydalanuvchiga bog\'langan.', [
                'provider' => $provider->label(),
            ]);
        }

        $current = $user->socialAccounts()->where('provider', $provider)->first();

        if ($current !== null && $current->provider_user_id !== $oauth->id) {
            throw OAuthException::translated('Hisobingizga boshqa :provider akkaunti bog\'langan. Avval uni uzing.', [
                'provider' => $provider->label(),
            ]);
        }

        $account = $current ?? new SocialAccount;
        $isNew = ! $account->exists;

        $account->forceFill([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $oauth->id,
            'email' => $oauth->email,
        ])->save();

        if ($isNew) {
            $this->audit->log(
                AuditEvent::SocialLinked,
                $user,
                ['provider' => $provider->value, 'email' => $oauth->email],
                $provider->label(),
                $user,
            );
        }

        $this->syncOrcid($user, $oauth);

        return $account;
    }

    /**
     * @throws OAuthException
     */
    public function unlink(User $user, SocialProvider $provider): void
    {
        $account = $user->socialAccounts()->where('provider', $provider)->first();

        if ($account === null) {
            return;
        }

        if (! $user->hasPassword() && $user->socialAccounts()->count() <= 1) {
            throw OAuthException::translated('Bu hisobingizga kirishning yagona usuli. Avval parol o\'rnating, keyin uzing.');
        }

        $account->delete();

        $this->audit->log(
            AuditEvent::SocialUnlinked,
            $user,
            ['provider' => $provider->value],
            $provider->label(),
            $user,
        );
    }

    /**
     * Yangi muallif (parolsiz). Email provayder tasdiqlagan bo'lsa — darhol tasdiqlangan,
     * aks holda tasdiqlash havolasi yuboriladi.
     *
     * @param  array{last_name: string, first_name: string, phone: string, email: string}  $data
     */
    public function register(SocialProvider $provider, OAuthUser $oauth, array $data): User
    {
        $verified = $oauth->emailVerified && $oauth->email === $data['email'];

        $user = DB::transaction(function () use ($provider, $oauth, $data, $verified): User {
            $user = User::create([
                'name' => trim($data['last_name'].' '.$data['first_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => null,
                'locale' => app()->getLocale(),
            ]);

            if ($verified) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->authorProfile()->create([
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
            ]);

            $user->assignRole(RoleName::Author);

            $this->link($user, $provider, $oauth);

            return $user;
        });

        // Tasdiqlanmagan email — MustVerifyEmail tasdiqlash xatini yuboradi
        event(new Registered($user));

        return $user;
    }

    /**
     * @throws OAuthException
     */
    private function ensureCanSignIn(?User $user): void
    {
        if ($user === null || $user->trashed()) {
            throw OAuthException::translated('Bu hisob o\'chirilgan. Tahririyat bilan bog\'laning.');
        }

        if ($user->is_blocked) {
            throw OAuthException::translated('Hisobingiz bloklangan. Tahririyat bilan bog\'laning.');
        }
    }

    /**
     * Tasdiqlanmagan hisob: emailni hech kim tasdiqlamagan, demak parolni boshqa odam (masalan,
     * shu email bilan oldindan ro'yxatdan o'tgan begona) qo'ygan bo'lishi mumkin. Email egasi
     * provayder orqali kelganda — begona parol, "eslab qolish" tokeni, 2FA va boshqa sessiyalar bekor qilinadi.
     */
    private function claimUnverifiedAccount(User $user): void
    {
        $user->forceFill([
            'email_verified_at' => now(),
            'password' => null,
            'remember_token' => Str::random(60),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        $this->audit->log(
            AuditEvent::UserPasswordChanged,
            $user,
            ['reason' => 'social_claim'],
            'Tasdiqlanmagan hisob tashqi akkaunt orqali tasdiqlandi, eski parol bekor qilindi',
            $user,
        );
    }

    /** ORCID orqali tasdiqlangan iD — profil bo'sh bo'lsa va boshqa profilda bo'lmasa yoziladi */
    private function syncOrcid(User $user, OAuthUser $oauth): void
    {
        if ($oauth->orcid === null) {
            return;
        }

        $profile = $user->authorProfile()->first();

        if ($profile === null || ($profile->orcid !== null && $profile->orcid !== '')) {
            return;
        }

        $taken = AuthorProfile::query()
            ->where('orcid', $oauth->orcid)
            ->where('id', '!=', $profile->id)
            ->exists();

        if (! $taken) {
            $profile->forceFill(['orcid' => $oauth->orcid])->save();
        }
    }
}
