<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\BannerRequest;
use App\Models\Banner;
use App\Models\User;
use App\Services\Content\BannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;

/**
 * Bosh sahifa bannerlari. Yangilash rasm bilan yuborilgani uchun POST (+ _method=put).
 */
class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function store(BannerRequest $request): RedirectResponse
    {
        $this->banners->save(null, BannerService::data($request->validated()), $this->image($request), $this->user($request));

        return $this->done(__("Banner qo'shildi."));
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $this->banners->save($banner, BannerService::data($request->validated()), $this->image($request), $this->user($request));

        return $this->done(__('Banner saqlandi.'));
    }

    public function destroy(Request $request, Banner $banner): RedirectResponse
    {
        $this->banners->delete($banner, $this->user($request));

        return $this->done(__("Banner o'chirildi."));
    }

    private function image(Request $request): ?UploadedFile
    {
        $file = $request->file('image');

        return $file instanceof UploadedFile ? $file : null;
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
