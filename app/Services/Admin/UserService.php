<?php

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\User;
use App\Services\Users\AvatarService;
use App\Services\Users\ProfileService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin panel — foydalanuvchilarni boshqarish (TZ 4.2.4).
 *
 * Biznes qoidalari shu yerda (Gate::before Super Admin'ga hamma ruxsatni
 * beradi, shuning uchun "o'zini o'chira olmaydi" kabi cheklovlar policy'da emas):
 *   - o'zini o'chirish / bloklash / o'zidan Super Admin rolini olish mumkin emas;
 *   - Super Admin rolini faqat Super Admin bera oladi yoki olib tashlay oladi.
 */
class UserService
{
    public const PER_PAGE = 15;

    public const STATUSES = ['active', 'blocked', 'unverified', 'deleted'];

    public const SORTS = ['latest', 'oldest', 'name', 'last_login'];

    public function __construct(
        private readonly ProfileService $profiles,
        private readonly AvatarService $avatars,
    ) {}

    /**
     * Ro'yxat: qidiruv (ism, email, telefon), rol, holat va tartib bo'yicha.
     *
     * @param  array{search?: string|null, role?: string|null, status?: string|null, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with(['roles', 'authorProfile']);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('authorProfile', fn (Builder $p) => $p
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('organization', 'like', $like));
            });
        }

        // "staff" — admin panel rollaridan istalgani
        if (($filters['role'] ?? null) === 'staff') {
            $query->staff();
        } elseif (($role = RoleName::tryFrom((string) ($filters['role'] ?? ''))) !== null) {
            $query->role($role->value);
        }

        switch ($filters['status'] ?? null) {
            case 'active':
                $query->where('is_blocked', false);
                break;
            case 'blocked':
                $query->where('is_blocked', true);
                break;
            case 'unverified':
                $query->whereNull('email_verified_at');
                break;
            case 'deleted':
                $query->onlyTrashed();
                break;
        }

        switch ($filters['sort'] ?? 'latest') {
            case 'oldest':
                $query->oldest()->oldest('id');
                break;
            case 'name':
                $query->orderBy('name');
                break;
            case 'last_login':
                $query->orderByRaw('last_login_at is null')->latest('last_login_at');
                break;
            default:
                $query->latest()->latest('id');
        }

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Yuqoridagi statistika kartalari.
     *
     * @return array{total: int, staff: int, authors: int, blocked: int, deleted: int}
     */
    public function counts(): array
    {
        return [
            'total' => User::query()->count(),
            'staff' => User::query()->staff()->count(),
            'authors' => User::query()->role(RoleName::Author->value)->count(),
            'blocked' => User::query()->where('is_blocked', true)->count(),
            'deleted' => User::query()->onlyTrashed()->count(),
        ];
    }

    /**
     * Profil sahifasidagi faoliyat ko'rsatkichlari.
     *
     * @return array{articles: int, publishedArticles: int, paidTotal: int, aiRequests: int}
     */
    public function activity(User $user): array
    {
        return [
            'articles' => $user->submittedArticles()->count(),
            'publishedArticles' => $user->submittedArticles()->published()->count(),
            'paidTotal' => (int) round((float) $user->payments()
                ->where('status', PaymentStatus::Paid->value)
                ->sum('amount')),
            'aiRequests' => $user->aiRequests()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data  StoreUserRequest::validated()
     */
    public function create(array $data, User $actor, ?UploadedFile $avatar = null): User
    {
        $roles = $this->rolesFrom($data);
        $this->guardRoleChange(null, $roles, $actor);

        $user = DB::transaction(function () use ($data, $roles): User {
            $user = new User;
            $user->forceFill([
                'name' => trim($data['last_name'].' '.$data['first_name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'locale' => $data['locale'],
                'email_verified_at' => ! empty($data['email_verified']) ? now() : null,
            ])->save();

            $user->authorProfile()->create([
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
                'is_public' => in_array(RoleName::Author, $roles, true),
            ]);

            $user->load('authorProfile');

            $this->profiles->updatePersonal($user, $data, keepVerified: true);
            $this->profiles->updateAcademic($user, $data);
            $user->syncRoles(array_map(fn (RoleName $r): string => $r->value, $roles));

            return $user;
        });

        if ($avatar !== null) {
            $this->avatars->store($user, $avatar);
        }

        // "Email tasdiqlangan" belgilanmagan bo'lsa — foydalanuvchiga tasdiqlash xati
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data  UpdateUserRequest::validated()
     */
    public function update(User $user, array $data, User $actor, ?UploadedFile $avatar = null): void
    {
        $roles = $this->rolesFrom($data);
        $this->guardRoleChange($user, $roles, $actor);

        $emailChanged = DB::transaction(function () use ($user, $data, $roles): bool {
            $verified = ! empty($data['email_verified']);

            $emailChanged = $this->profiles->updatePersonal($user, $data, keepVerified: $verified);
            $this->profiles->updateAcademic($user, $data);

            // "Email tasdiqlangan" belgisi
            if ($verified && $user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            } elseif (! $verified && $user->email_verified_at !== null) {
                $user->forceFill(['email_verified_at' => null])->save();
            }

            $user->syncRoles(array_map(fn (RoleName $r): string => $r->value, $roles));

            return $emailChanged;
        });

        if ($avatar !== null) {
            $this->avatars->store($user, $avatar);
        }

        // Yangi (tasdiqlanmagan) manzilga tasdiqlash xati
        if ($emailChanged && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }

    public function block(User $user, User $actor, ?string $reason): void
    {
        $this->guardNotSelf($user, $actor, __("O'zingizni bloklay olmaysiz."));

        $user->block($reason);
    }

    public function unblock(User $user): void
    {
        $user->unblock();
    }

    public function delete(User $user, User $actor): void
    {
        $this->guardNotSelf($user, $actor, __("O'zingizni o'chira olmaysiz."));

        $user->delete();
    }

    public function restore(User $user): void
    {
        $user->restore();
    }

    public function setPassword(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, RoleName>
     */
    private function rolesFrom(array $data): array
    {
        $values = is_array($data['roles'] ?? null) ? $data['roles'] : [];

        return array_values(array_filter(array_map(
            fn (mixed $value): ?RoleName => is_string($value) ? RoleName::tryFrom($value) : null,
            $values,
        )));
    }

    /**
     * @param  array<int, RoleName>  $roles
     */
    private function guardRoleChange(?User $user, array $roles, User $actor): void
    {
        $grantsSuperAdmin = in_array(RoleName::SuperAdmin, $roles, true);
        $hadSuperAdmin = $user?->isSuperAdmin() ?? false;

        if (($grantsSuperAdmin || $hadSuperAdmin) && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'roles' => __('Bosh administrator rolini faqat Bosh administrator boshqara oladi.'),
            ]);
        }

        if ($user !== null && $user->is($actor) && $hadSuperAdmin && ! $grantsSuperAdmin) {
            throw ValidationException::withMessages([
                'roles' => __("O'zingizdan Bosh administrator rolini olib tashlay olmaysiz."),
            ]);
        }
    }

    private function guardNotSelf(User $user, User $actor, string $message): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['user' => $message]);
        }
    }
}
