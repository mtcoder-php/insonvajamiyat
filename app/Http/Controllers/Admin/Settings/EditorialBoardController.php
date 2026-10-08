<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\Settings\Concerns\SettingsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\EditorialBoardRequest;
use App\Models\EditorialBoardMember;
use App\Services\Content\EditorialBoardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tahririyat kengashi a'zolari. Yangilash — POST (+ _method=put), rasm bilan.
 */
class EditorialBoardController extends Controller
{
    use SettingsActions;

    public function __construct(private readonly EditorialBoardService $board) {}

    public function store(EditorialBoardRequest $request): RedirectResponse
    {
        $this->board->save(null, EditorialBoardService::data($request->validated()), $this->file($request, 'photo'), false, $this->user($request));

        return $this->done(__("A'zo qo'shildi."));
    }

    public function update(EditorialBoardRequest $request, EditorialBoardMember $member): RedirectResponse
    {
        $this->board->save($member, EditorialBoardService::data($request->validated()), $this->file($request, 'photo'), $request->boolean('remove_photo'), $this->user($request));

        return $this->done(__("A'zo ma'lumotlari saqlandi."));
    }

    public function destroy(Request $request, EditorialBoardMember $member): RedirectResponse
    {
        $this->board->delete($member, $this->user($request));

        return $this->done(__("A'zo o'chirildi."));
    }
}
