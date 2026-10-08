<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\PageSlug;
use App\Http\Controllers\Admin\Settings\Concerns\SettingsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\PageRequest;
use App\Services\Content\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Statik sahifalar ("Jurnal haqida", "Yo'riqnoma", "Aloqa") matnini tahrirlash.
 */
class PageController extends Controller
{
    use SettingsActions;

    public function __construct(private readonly PageService $pages) {}

    public function update(PageRequest $request, PageSlug $page): RedirectResponse
    {
        $this->pages->save($page, PageService::data($request->validated()), $this->user($request));

        return $this->done(__('Sahifa saqlandi.'));
    }

    public function reset(Request $request, PageSlug $page): RedirectResponse
    {
        $this->pages->reset($page, $this->user($request));

        return $this->done(__('Sahifa standart matnga qaytarildi.'));
    }
}
