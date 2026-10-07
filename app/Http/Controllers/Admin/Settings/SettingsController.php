<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Content\SettingsWorkspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Sozlamalar: ?tab=subjects|types|banners|posts|events|books|partners.
 * "Maqola turlari va narxlar" — qo'shimcha prices.manage ruxsati bilan.
 * Yangiliklar va tadbirlar sahifalangan (?page, ?q, ?type, ?when).
 */
class SettingsController extends Controller
{
    public const TABS = ['subjects', 'types', 'banners', 'posts', 'events', 'books', 'partners'];

    public function index(Request $request, SettingsWorkspace $workspace): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canPrices = $user->can(PermissionName::PricesManage->value);

        $tabs = $canPrices ? self::TABS : array_values(array_diff(self::TABS, ['types']));
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, $tabs, true) ? $tab : 'subjects';

        $filters = SettingsWorkspace::filters($request->query->all());
        $page = max(1, $request->integer('page', 1));

        return Inertia::render('admin/settings/Index', [
            'tab' => $tab,
            'tabs' => $tabs,
            'filters' => $filters,
            'subjects' => $tab === 'subjects' ? fn (): array => $workspace->subjects() : null,
            'types' => $tab === 'types' ? fn (): array => $workspace->articleTypes() : null,
            'banners' => $tab === 'banners' ? fn (): array => $workspace->banners() : null,
            'posts' => $tab === 'posts' ? fn (): array => $workspace->posts($filters, $page) : null,
            'events' => $tab === 'events' ? fn (): array => $workspace->events($filters, $page) : null,
            'books' => $tab === 'books' ? fn (): array => $workspace->books() : null,
            'partners' => $tab === 'partners' ? fn (): array => $workspace->partners() : null,
            'partnerTypes' => $tab === 'partners' ? SettingsWorkspace::partnerTypes() : null,
            'urls' => [
                'index' => route('admin.settings.index'),
                'subjects' => route('admin.settings.subjects.store'),
                'types' => $canPrices ? route('admin.settings.types.store') : null,
                'banners' => route('admin.settings.banners.store'),
                'posts' => route('admin.settings.posts.store'),
                'events' => route('admin.settings.events.store'),
                'books' => route('admin.settings.books.store'),
                'partners' => route('admin.settings.partners.store'),
            ],
        ]);
    }
}
