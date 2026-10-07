<?php

namespace App\Http\Controllers\Admin\System;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\RoleMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Rollar va ruxsatlar: matritsa, rol ruxsatlarini saqlash va standartga qaytarish.
 */
class RoleController extends Controller
{
    public function __construct(private readonly RoleMatrix $matrix) {}

    public function index(): Response
    {
        return Inertia::render('admin/roles/Index', $this->matrix->matrix());
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $name = $this->role($role);
        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct'],
        ]);

        /** @var array<int, string> $permissions */
        $permissions = array_values(array_filter((array) $data['permissions'], 'is_string'));
        $this->matrix->update($name, $permissions, $this->user($request));

        return $this->done(__(':role roli ruxsatlari saqlandi.', ['role' => $name->label()]));
    }

    public function reset(Request $request, string $role): RedirectResponse
    {
        $name = $this->role($role);
        $this->matrix->reset($name, $this->user($request));

        return $this->done(__(':role roli standart ruxsatlarga qaytarildi.', ['role' => $name->label()]));
    }

    private function role(string $value): RoleName
    {
        $name = RoleName::tryFrom($value);
        abort_if($name === null, 404);

        return $name;
    }

    private function done(mixed $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
