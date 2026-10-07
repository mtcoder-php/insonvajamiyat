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
 * Admin → Sozlamalar: ?tab=subjects|types|banners.
 * "Maqola turlari va narxlar" — qo'shimcha prices.manage ruxsati bilan.
 */
class SettingsController extends Controller
{
    public const TABS = ['subjects', 'types', 'banners'];

    public function index(Request $request, SettingsWorkspace $workspace): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canPrices = $user->can(PermissionName::PricesManage->value);

        $tabs = $canPrices ? self::TABS : array_values(array_diff(self::TABS, ['types']));
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, $tabs, true) ? $tab : 'subjects';

        return Inertia::render('admin/settings/Index', [
            'tab' => $tab,
            'tabs' => $tabs,
            'subjects' => $tab === 'subjects' ? fn (): array => $workspace->subjects() : null,
            'types' => $tab === 'types' ? fn (): array => $workspace->articleTypes() : null,
            'banners' => $tab === 'banners' ? fn (): array => $workspace->banners() : null,
            'urls' => [
                'index' => route('admin.settings.index'),
                'subjects' => route('admin.settings.subjects.store'),
                'types' => $canPrices ? route('admin.settings.types.store') : null,
                'banners' => route('admin.settings.banners.store'),
            ],
        ]);
    }
}
