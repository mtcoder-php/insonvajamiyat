<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Indexing\OaiPmh\OaiPmhServer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * OAI-PMH 2.0 endpoint: /oai (GET yoki POST, application/x-www-form-urlencoded).
 * Sessiya, CSRF va Inertia'siz (routes/oai.php) — harvester'lar uchun yengil.
 */
class OaiPmhController extends Controller
{
    public function __invoke(Request $request, OaiPmhServer $server): Response
    {
        // Xom so'rov satri: takrorlangan argumentlarni aniqlash uchun (getQueryString() ularni saralaydi)
        $query = $request->server->get('QUERY_STRING');
        $raw = $request->isMethod('POST') ? $request->getContent() : (is_string($query) ? $query : '');

        return response($server->handle(self::pairs($raw), route('oai')), 200, [
            'Content-Type' => 'text/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Argumentlar ro'yxati — PHP takrorlangan kalitlarni birlashtirib yuboradi,
     * OAI-PMH esa takrorni badArgument deb qaytarishni talab qiladi.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function pairs(string $raw): array
    {
        $pairs = [];

        foreach (explode('&', $raw) as $chunk) {
            if ($chunk === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $chunk, 2), 2, '');
            $pairs[] = [urldecode($key), urldecode($value)];
        }

        return $pairs;
    }
}
