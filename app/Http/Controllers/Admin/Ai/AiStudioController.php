<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Enums\PermissionName;
use App\Http\Controllers\Concerns\AiStudioActions;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Ai\AiUsageOverview;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → AI Studio (super admin ai page.png):
 *   ?tab=proofreader|translator|analytics|history|settings, ?request={uuid} — tanlangan natija.
 * Natija tayyorlanayotganda frontend faqat "current" ni qayta so'raydi (usePoll).
 * So'rov amallari — AiStudioActions (kabinet bilan umumiy).
 */
class AiStudioController extends Controller
{
    use AiStudioActions;

    public const TABS = ['proofreader', 'translator', 'analytics', 'history', 'settings'];

    protected function aiRoutes(): string
    {
        return 'admin.ai';
    }

    public function index(Request $request, AiUsageOverview $usage): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canManage = $user->can(PermissionName::AiSettingsManage->value);

        $tabs = $canManage ? self::TABS : array_values(array_diff(self::TABS, ['settings']));
        $props = $this->studioProps($request, $user, $tabs, $canManage);

        return Inertia::render('admin/ai/Index', [
            ...$props,
            'articles' => null,
            'settings' => $props['tab'] === 'settings' ? fn (): array => $usage->settings($request->string('uq')->toString()) : null,
        ]);
    }
}
