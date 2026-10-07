<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\ArticleTypeRequest;
use App\Models\ArticleType;
use App\Models\User;
use App\Services\Content\ArticleTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleTypeController extends Controller
{
    public function __construct(private readonly ArticleTypeService $types) {}

    public function store(ArticleTypeRequest $request): RedirectResponse
    {
        $this->types->save(null, ArticleTypeService::data($request->validated()), $this->user($request));

        return $this->done(__("Maqola turi qo'shildi."));
    }

    public function update(ArticleTypeRequest $request, ArticleType $articleType): RedirectResponse
    {
        $this->types->save($articleType, ArticleTypeService::data($request->validated()), $this->user($request));

        return $this->done(__('Maqola turi saqlandi.'));
    }

    public function destroy(Request $request, ArticleType $articleType): RedirectResponse
    {
        $this->types->delete($articleType, $this->user($request));

        return $this->done(__("Maqola turi o'chirildi."));
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function done(mixed $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
