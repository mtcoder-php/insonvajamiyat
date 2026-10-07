<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\Settings\Concerns\SettingsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\EventRequest;
use App\Models\Event;
use App\Services\Content\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tadbirlar (konferensiya, seminar). Yangilash — POST (+ _method=put).
 */
class EventController extends Controller
{
    use SettingsActions;

    public function __construct(private readonly EventService $events) {}

    public function store(EventRequest $request): RedirectResponse
    {
        $this->events->save(null, EventService::data($request->validated()), $this->file($request, 'image'), false, $this->user($request));

        return $this->done(__("Tadbir qo'shildi."));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $this->events->save($event, EventService::data($request->validated()), $this->file($request, 'image'), $request->boolean('remove_image'), $this->user($request));

        return $this->done(__('Tadbir saqlandi.'));
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->events->delete($event, $this->user($request));

        return $this->done(__("Tadbir o'chirildi."));
    }
}
