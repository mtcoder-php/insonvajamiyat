<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\Settings\Concerns\SettingsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\JournalDocumentRequest;
use App\Models\JournalDocument;
use App\Services\Content\JournalDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin → Sozlamalar → Fayllar: maqola shabloni, yo'riqnoma va shakllar.
 * Yangilash — POST (+ _method=put), fayl bilan.
 */
class JournalDocumentController extends Controller
{
    use SettingsActions;

    public function __construct(private readonly JournalDocumentService $documents) {}

    public function store(JournalDocumentRequest $request): RedirectResponse
    {
        $this->documents->save(null, JournalDocumentService::data($request->validated()), $this->file($request, 'file'), $this->user($request));

        return $this->done(__('Fayl yuklandi.'));
    }

    public function update(JournalDocumentRequest $request, JournalDocument $document): RedirectResponse
    {
        $this->documents->save($document, JournalDocumentService::data($request->validated()), $this->file($request, 'file'), $this->user($request));

        return $this->done(__('Fayl ma\'lumotlari saqlandi.'));
    }

    public function destroy(Request $request, JournalDocument $document): RedirectResponse
    {
        $this->documents->delete($document, $this->user($request));

        return $this->done(__("Fayl o'chirildi."));
    }
}
