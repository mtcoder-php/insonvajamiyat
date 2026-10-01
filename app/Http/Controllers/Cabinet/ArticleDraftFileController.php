<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cabinet\Articles\ArticleFileRequest;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\User;
use App\Services\Articles\ArticleSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Yangi maqola formasi, 5-bosqich: qoralamaga fayl yuklash va o'chirish.
 */
class ArticleDraftFileController extends Controller
{
    public function __construct(private readonly ArticleSubmissionService $submission) {}

    public function store(ArticleFileRequest $request, Article $article): RedirectResponse
    {
        $type = $request->fileType();
        $file = $request->file('file');
        abort_if($type === null || ! $file instanceof UploadedFile, 422);

        /** @var User $user */
        $user = $request->user();

        $stored = $this->submission->uploadFile($article, $file, $type, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('«:name» yuklandi.', ['name' => $stored->original_name])]);

        return back();
    }

    public function destroy(Article $article, ArticleFile $file): RedirectResponse
    {
        abort_unless($article->status === ArticleStatus::Draft && Gate::allows('editDraft', $article), 403);
        abort_unless($file->article_id === $article->id, 404);

        $this->submission->deleteFile($article, $file);

        return back();
    }
}
