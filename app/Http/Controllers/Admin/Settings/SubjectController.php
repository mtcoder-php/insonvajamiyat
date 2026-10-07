<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\SubjectRequest;
use App\Models\Subject;
use App\Models\User;
use App\Services\Content\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function __construct(private readonly SubjectService $subjects) {}

    public function store(SubjectRequest $request): RedirectResponse
    {
        $this->subjects->save(null, SubjectService::data($request->validated()), $this->user($request));

        return $this->done(__("Yo'nalish qo'shildi."));
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->subjects->save($subject, SubjectService::data($request->validated()), $this->user($request));

        return $this->done(__("Yo'nalish saqlandi."));
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->subjects->delete($subject, $this->user($request));

        return $this->done(__("Yo'nalish o'chirildi."));
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
