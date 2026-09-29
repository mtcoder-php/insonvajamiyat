<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SubscribeNewsletterRequest;
use App\Services\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Footer'dagi "Obuna bo'lish" formasi.
 */
class NewsletterSubscriptionController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletter,
    ) {}

    public function store(SubscribeNewsletterRequest $request): RedirectResponse
    {
        $this->newsletter->subscribe(
            email: $request->string('email')->toString(),
            locale: app()->getLocale(),
            ip: $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __("Obuna bo'ldingiz! Jurnal yangiliklarini elektron pochtangizga yuboramiz."),
        ]);

        return back();
    }
}
