<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\Settings\Concerns\SettingsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\PartnerRequest;
use App\Models\Partner;
use App\Services\Content\PartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Hamkorlar va indekslash bazalari. Yangilash — POST (+ _method=put).
 */
class PartnerController extends Controller
{
    use SettingsActions;

    public function __construct(private readonly PartnerService $partners) {}

    public function store(PartnerRequest $request): RedirectResponse
    {
        $this->partners->save(null, PartnerService::data($request->validated()), $this->file($request, 'logo'), false, $this->user($request));

        return $this->done(__("Hamkor qo'shildi."));
    }

    public function update(PartnerRequest $request, Partner $partner): RedirectResponse
    {
        $this->partners->save($partner, PartnerService::data($request->validated()), $this->file($request, 'logo'), $request->boolean('remove_logo'), $this->user($request));

        return $this->done(__('Hamkor saqlandi.'));
    }

    public function destroy(Request $request, Partner $partner): RedirectResponse
    {
        $this->partners->delete($partner, $this->user($request));

        return $this->done(__("Hamkor o'chirildi."));
    }
}
