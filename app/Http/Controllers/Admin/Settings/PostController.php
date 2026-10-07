<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\Settings\Concerns\SettingsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\PostRequest;
use App\Models\Post;
use App\Services\Content\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Yangiliklar va e'lonlar. Yangilash rasm bilan yuborilgani uchun POST (+ _method=put).
 */
class PostController extends Controller
{
    use SettingsActions;

    public function __construct(private readonly PostService $posts) {}

    public function store(PostRequest $request): RedirectResponse
    {
        $this->posts->save(null, PostService::data($request->validated()), $this->file($request, 'image'), false, $this->user($request));

        return $this->done(__("Xabar qo'shildi."));
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $this->posts->save($post, PostService::data($request->validated()), $this->file($request, 'image'), $request->boolean('remove_image'), $this->user($request));

        return $this->done(__('Xabar saqlandi.'));
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->posts->delete($post, $this->user($request));

        return $this->done(__("Xabar o'chirildi."));
    }
}
