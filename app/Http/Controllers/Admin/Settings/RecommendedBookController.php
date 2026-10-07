<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\Settings\Concerns\SettingsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\RecommendedBookRequest;
use App\Models\RecommendedBook;
use App\Services\Content\RecommendedBookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tavsiya etilgan kitoblar. Yangilash — POST (+ _method=put).
 */
class RecommendedBookController extends Controller
{
    use SettingsActions;

    public function __construct(private readonly RecommendedBookService $books) {}

    public function store(RecommendedBookRequest $request): RedirectResponse
    {
        $this->books->save(null, RecommendedBookService::data($request->validated()), $this->file($request, 'cover'), false, $this->user($request));

        return $this->done(__("Kitob qo'shildi."));
    }

    public function update(RecommendedBookRequest $request, RecommendedBook $book): RedirectResponse
    {
        $this->books->save($book, RecommendedBookService::data($request->validated()), $this->file($request, 'cover'), $request->boolean('remove_cover'), $this->user($request));

        return $this->done(__('Kitob saqlandi.'));
    }

    public function destroy(Request $request, RecommendedBook $book): RedirectResponse
    {
        $this->books->delete($book, $this->user($request));

        return $this->done(__("Kitob o'chirildi."));
    }
}
