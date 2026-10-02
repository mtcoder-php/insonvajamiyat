<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Bildirishnomalar (header'dagi qo'ng'iroqcha va kabinetdagi "Xabarlar"):
 * bittasini ochish (o'qilgan bo'ladi va tegishli sahifaga o'tadi), hammasini o'qilgan qilish.
 */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationCenter $center) {}

    public function open(Request $request, string $notification): RedirectResponse
    {
        $url = $this->center->open($this->user($request), $notification);

        return $url !== null ? redirect()->to($url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->center->markAllRead($this->user($request));

        return back();
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
