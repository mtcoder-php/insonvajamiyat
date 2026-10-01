<?php

namespace App\Services\Users;

use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Profil rasmi: storage/app/public/avatars/{user_id}/... (public disk).
 * Yangisi yuklanganda eskisi o'chiriladi.
 */
class AvatarService
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function store(User $user, UploadedFile $file): string
    {
        $profile = $this->profiles->ensureProfile($user);
        $old = $profile->avatar_path;

        $path = $file->store("avatars/{$user->id}", MediaUrl::DISK);

        if ($path === false) {
            throw new \RuntimeException('Avatar could not be stored.');
        }

        $profile->forceFill(['avatar_path' => $path])->save();

        $this->deleteFile($old);

        return $path;
    }

    public function remove(User $user): void
    {
        $profile = $user->authorProfile;

        if ($profile === null || $profile->avatar_path === null) {
            return;
        }

        $this->deleteFile($profile->avatar_path);

        $profile->forceFill(['avatar_path' => null])->save();
    }

    private function deleteFile(?string $path): void
    {
        // Tashqi URL (masalan, Google rasm) bo'lsa — diskdan o'chirilmaydi
        if ($path === null || str_starts_with($path, 'http')) {
            return;
        }

        Storage::disk(MediaUrl::DISK)->delete($path);
    }
}
