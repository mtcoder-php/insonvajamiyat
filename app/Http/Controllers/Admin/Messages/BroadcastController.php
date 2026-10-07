<?php

namespace App\Http\Controllers\Admin\Messages;

use App\Enums\BroadcastAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Messages\BroadcastRequest;
use App\Models\User;
use App\Services\Messages\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Ommaviy xabar yuborish (users.manage). Yuborish navbatda bajariladi.
 */
class BroadcastController extends Controller
{
    public function store(BroadcastRequest $request, BroadcastService $broadcasts): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $broadcast = $broadcasts->send([
            'subject' => $request->string('subject')->trim()->toString(),
            'body' => $request->string('body')->trim()->toString(),
            'audience' => BroadcastAudience::from($request->string('audience')->toString()),
            'send_email' => $request->boolean('send_email'),
        ], $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Xabar :count ta foydalanuvchiga yuborilmoqda.', ['count' => $broadcast->recipients_count])]);

        return back();
    }
}
