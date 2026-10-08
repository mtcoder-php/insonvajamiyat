<?php

namespace App\Http\Controllers\Articles;

use App\Enums\MessageChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Articles\SendMessageRequest;
use App\Models\Article;
use App\Models\Message;
use App\Models\User;
use App\Services\Messages\ArticleMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Muallif ↔ tahririyat yozishmasi. Kabinet (cabinet.articles.messages.*) va admin panel
 * (admin.articles.messages.store) uchun umumiy — ruxsat ArticlePolicy::message orqali.
 */
class ArticleMessageController extends Controller
{
    public function __construct(private readonly ArticleMessageService $messages) {}

    public function store(SendMessageRequest $request, Article $article): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $attachment = $request->file('attachment');

        $this->messages->send(
            $article,
            $user,
            $request->string('body')->trim()->toString(),
            $attachment instanceof UploadedFile ? $attachment : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Xabar yuborildi.')]);

        return back();
    }

    public function attachment(Article $article, Message $message): StreamedResponse
    {
        Gate::authorize('message', $article);
        // Faqat muallif ↔ tahririyat yozishmasi fayllari (muharrir ↔ taqrizchi kanali emas)
        abort_unless($message->channel === MessageChannel::AuthorEditor, 404);

        return $this->messages->attachment($message);
    }
}
