<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\JournalSettingsRequest;
use App\Http\Requests\Admin\System\MailSettingsRequest;
use App\Models\User;
use App\Services\Settings\SystemSettings;
use App\Services\Settings\SystemStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Tizim sozlamalari: ?tab=journal|contacts|payment|mail|status.
 */
class SystemSettingsController extends Controller
{
    public const TABS = ['journal', 'contacts', 'payment', 'mail', 'status'];

    public function __construct(private readonly SystemSettings $settings) {}

    public function index(Request $request, SystemStatus $status): Response
    {
        $tab = $request->string('tab')->toString();

        return Inertia::render('admin/system/Index', [
            'tab' => in_array($tab, self::TABS, true) ? $tab : 'journal',
            ...$this->settings->form(),
            'status' => fn (): array => $status->overview(),
            'urls' => [
                'index' => route('admin.system.index'),
                'journal' => route('admin.system.journal'),
                'mail' => route('admin.system.mail'),
                'mailTest' => route('admin.system.mail.test'),
            ],
        ]);
    }

    public function journal(JournalSettingsRequest $request): RedirectResponse
    {
        $changed = $this->settings->update('journal', $request->validated(), $this->user($request));

        return $this->done($changed === [] ? __("O'zgarish yo'q.") : __('Jurnal ma\'lumotlari saqlandi.'));
    }

    public function mail(MailSettingsRequest $request): RedirectResponse
    {
        $changed = $this->settings->update('mail', $request->validated(), $this->user($request));

        return $this->done($changed === [] ? __("O'zgarish yo'q.") : __('Pochta sozlamalari saqlandi.'));
    }

    public function mailTest(Request $request): RedirectResponse
    {
        $data = $request->validate(['test_email' => ['required', 'email', 'max:255']]);
        $this->settings->sendTest((string) $data['test_email'], $this->user($request));

        return $this->done(__('Test xat :email manziliga yuborildi. Pochta qutisini (va «Spam» papkasini) tekshiring.', ['email' => $data['test_email']]));
    }

    private function done(mixed $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
