<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\EventResource;
use App\Models\Event;
use App\Support\Seo\SeoMeta;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tadbirlar: ro'yxat (/events — yaqinlashayotgan va o'tgan) va batafsil (/events/{slug}).
 */
class EventController extends Controller
{
    public function index(): Response
    {
        $upcoming = Event::query()->published()->upcoming()->get();

        $past = Event::query()
            ->published()
            ->whereNotIn('id', $upcoming->modelKeys())
            ->orderByDesc('starts_at')
            ->limit(12)
            ->get();

        return Inertia::render('web/events/Index', [
            'hero' => StaticPageController::hero('events'),
            'upcoming' => EventResource::collection($upcoming)->resolve(),
            'past' => EventResource::collection($past)->resolve(),
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->is_published, 404);
        app(SeoMeta::class)->forEvent($event);

        $others = Event::query()
            ->published()
            ->upcoming()
            ->whereKeyNot($event->id)
            ->limit(4)
            ->get();

        return Inertia::render('web/events/Show', [
            'hero' => StaticPageController::hero('events'),
            'event' => [
                ...EventResource::make($event)->resolve(),
                'description' => $event->description,
                'isPast' => ($event->ends_at ?? $event->starts_at)->endOfDay()->isPast(),
            ],
            'others' => EventResource::collection($others)->resolve(),
        ]);
    }
}
