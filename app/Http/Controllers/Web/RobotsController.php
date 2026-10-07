<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * /robots.txt — production'da ommaviy qism ochiq, admin/kabinet/kirish sahifalari yopiq.
 * Test (staging) va lokal muhitda butun sayt indeksatsiyadan yopiladi.
 */
class RobotsController extends Controller
{
    private const DISALLOW = [
        '/admin',
        '/cabinet',
        '/settings',
        '/dashboard',
        '/notifications',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/two-factor-challenge',
        '/email',
        '/payments',
        '/locale',
        '/newsletter',
    ];

    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->isProduction()) {
            foreach (self::DISALLOW as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            $lines[] = 'Allow: /';
            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
