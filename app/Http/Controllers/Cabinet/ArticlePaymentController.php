<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\Payments\OnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Muallif "Click orqali to'lash" / "Payme orqali to'lash" ni bosadi →
 * to'lov urinishi yaratiladi va to'lov tizimi sahifasiga yo'naltiriladi.
 */
class ArticlePaymentController extends Controller
{
    public function store(Request $request, Article $article, OnlinePaymentService $payments): Response
    {
        Gate::authorize('pay', $article);

        $request->validate([
            'provider' => ['required', Rule::in(array_map(fn (PaymentProvider $p): string => $p->value, PaymentProvider::online()))],
        ]);

        /** @var User $user */
        $user = $request->user();

        $url = $payments->start($article, $user, PaymentProvider::from($request->string('provider')->toString()));

        return Inertia::location($url);
    }
}
