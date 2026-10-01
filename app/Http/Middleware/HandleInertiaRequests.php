<?php

namespace App\Http\Middleware;

use App\Enums\Language;
use App\Models\User;
use App\Services\Admin\NavigationBadges;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => fn () => $this->authPayload($user),
            'locale' => fn () => app()->getLocale(),
            'locales' => fn () => array_map(fn (Language $language): array => [
                'code' => $language->value,
                'label' => $language->label(),
            ], Language::cases()),
            'journal' => fn () => $this->journalPayload(),
            'notifications' => fn () => $user instanceof User
                ? ['unread' => $user->unreadNotifications()->count()]
                : null,
            // Admin sidebar raqamlari — faqat admin panel sahifalarida hisoblanadi
            'adminBadges' => fn () => $user instanceof User && $request->routeIs('admin.*')
                ? app(NavigationBadges::class)->for($user)
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Header/footer uchun jurnal rekvizitlari (config/journal.php).
     * Bo'sh ijtimoiy tarmoq havolalari yuborilmaydi.
     *
     * @return array<string, mixed>
     */
    private function journalPayload(): array
    {
        return [
            'name' => config('journal.name'),
            'subtitle' => config('journal.subtitle'),
            'description' => config('journal.description'),
            'issn' => config('journal.issn'),
            'eissn' => config('journal.eissn'),
            'doiPrefix' => config('journal.doi_prefix'),
            'frequency' => config('journal.frequency'),
            'contact' => config('journal.contact'),
            'socials' => array_filter((array) config('journal.socials')),
        ];
    }

    /**
     * Frontend faqat menyu va tugmalarni ko'rsatish/yashirish uchun rollarni biladi.
     * Haqiqiy himoya har doim serverda (middleware, Policy).
     *
     * @return array<string, mixed>
     */
    private function authPayload(?User $user): array
    {
        if ($user === null) {
            return ['user' => null, 'roles' => [], 'permissions' => [], 'isStaff' => false];
        }

        // Profil rasmi URL'i header va menyularda ishlatiladi (author_profiles.avatar_path)
        $userData = $user->toArray();
        $userData['avatar'] = $user->avatarUrl();
        // Kabinet sidebaridagi profil kartasi uchun (author_profiles.position)
        $userData['position'] = $user->authorProfile?->position;

        return [
            'user' => $userData,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->isSuperAdmin()
                ? ['*']
                : $user->getAllPermissions()->pluck('name')->values(),
            'isStaff' => $user->isStaff(),
        ];
    }
}
