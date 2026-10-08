<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Notifications\Web\ContactMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

/**
 * "Aloqa" sahifasidagi forma → tahririyat pochtasi (journal.contact.email).
 * Spamdan himoya: IP bo'yicha chegara (ThrottleAccountForms) va yashirin "website" maydoni.
 */
class ContactMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'string', 'max:0'],
        ], [], [
            'name' => __('Ismingiz'),
            'email' => __('Elektron pochta'),
            'subject' => __('Mavzu'),
            'message' => __('Xabar'),
        ]);

        $to = config('journal.contact.email');

        if (is_string($to) && $to !== '') {
            Notification::route('mail', $to)->notify(new ContactMessageNotification(
                name: trim($data['name']),
                email: mb_strtolower(trim($data['email'])),
                subject: isset($data['subject']) && trim($data['subject']) !== '' ? trim($data['subject']) : null,
                body: trim($data['message']),
                ip: $request->ip(),
            ));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Xabaringiz yuborildi. Tahririyat tez orada javob beradi.'),
        ]);

        return back();
    }
}
