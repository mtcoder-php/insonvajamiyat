<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * Admin panel — foydalanuvchi profili va tahrirlash formasi uchun to'liq ma'lumot.
 *
 * @mixin User
 */
class UserDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->authorProfile;
        $actor = $request->user();
        $isSelf = $actor instanceof User && $actor->is($this->resource);

        return [
            ...UserListResource::make($this->resource)->resolve($request),
            'lastName' => $profile->last_name ?? $this->name,
            'firstName' => $profile->first_name ?? '',
            'middleName' => $profile?->middle_name,
            'fullName' => $profile->full_name ?? $this->name,
            'locale' => $this->locale,
            'position' => $profile?->position,
            'department' => $profile?->department,
            'academicDegree' => $profile?->academic_degree,
            'academicTitle' => $profile?->academic_title,
            'orcid' => $profile?->orcid,
            'city' => $profile?->city,
            'bio' => $profile?->bio,
            'blockedAt' => $this->blocked_at?->toIso8601String(),
            'blockedReason' => $this->blocked_reason,
            'lastLoginIp' => $this->last_login_ip,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'twoFactorEnabled' => $this->two_factor_confirmed_at !== null,
            'isSelf' => $isSelf,
            'can' => [
                'update' => Gate::allows('update', $this->resource),
                'delete' => ! $isSelf && Gate::allows('delete', $this->resource),
                'block' => ! $isSelf && Gate::allows('block', $this->resource),
            ],
        ];
    }
}
