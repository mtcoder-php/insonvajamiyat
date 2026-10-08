<?php

use App\Http\Controllers\Web\OaiPmhController;
use Illuminate\Support\Facades\Route;

/*
| OAI-PMH 2.0 — ilmiy bazalar (BASE, OpenAIRE, CyberLeninka, Google Scholar) uchun metadata.
| Sessiya va CSRF'siz: harvester'lar cookie yubormaydi.
*/
Route::match(['get', 'post'], 'oai', OaiPmhController::class)->name('oai');
