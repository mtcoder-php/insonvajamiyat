<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JournalDocument;
use App\Services\Content\JournalDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mualliflar uchun faylni yuklab olish (shablon, yo'riqnoma, shakl) — faqat faol fayllar.
 */
class JournalDocumentController extends Controller
{
    public function __invoke(JournalDocument $document, JournalDocumentService $documents): StreamedResponse
    {
        abort_unless($document->is_active, 404);

        return $documents->download($document);
    }
}
