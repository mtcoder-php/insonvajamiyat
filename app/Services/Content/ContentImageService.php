<?php

namespace App\Services\Content;

use App\Models\ContentImage;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use App\Support\MediaUrl;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Matn muharriri rasmlari: yuklash (GD orqali qayta kodlanadi — EXIF/GPS va yashirin
 * ma'lumotlar olib tashlanadi, katta rasm MAX_WIDTH gacha kichraytiriladi) va hech qaysi
 * yangilik/tadbir matnida ishlatilmay qolganlarini tozalash.
 */
class ContentImageService
{
    public const DIR = 'content-images';

    public const MAX_WIDTH = 1600;

    /** Yuklangandan keyin shuncha soat ichida ishlatilmasa — o'chiriladi */
    public const ORPHAN_AFTER_HOURS = 24;

    public function store(UploadedFile $file, ?User $user): ContentImage
    {
        $binary = (string) file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($binary);

        if (! $source instanceof GdImage) {
            throw ValidationException::withMessages(['image' => __("Rasmni o'qib bo'lmadi. JPG, PNG yoki WEBP yuklang.")]);
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width > self::MAX_WIDTH) {
            $height = (int) round($height * self::MAX_WIDTH / $width);
            $width = self::MAX_WIDTH;
            $scaled = imagescale($source, $width, $height, IMG_BICUBIC);
            $source = $scaled instanceof GdImage ? $scaled : $source;
        }

        $transparent = strtolower($file->getClientOriginalExtension()) === 'png' || $file->getMimeType() === 'image/png';
        [$data, $extension] = $this->encode($source, $transparent);

        $path = self::DIR.'/'.now()->format('Y/m').'/'.Str::lower(Str::random(20)).'.'.$extension;
        Storage::disk(MediaUrl::DISK)->put($path, $data, 'public');

        return ContentImage::query()->create([
            'path' => $path,
            'user_id' => $user?->id,
            'width' => $width,
            'height' => $height,
            'size' => strlen($data),
            'created_at' => now(),
        ]);
    }

    /**
     * Hech qaysi yangilik (o'chirilganlari ham) yoki tadbir matnida uchramaydigan,
     * ORPHAN_AFTER_HOURS dan eski rasmlarni o'chiradi.
     *
     * @return int o'chirilgan rasmlar soni
     */
    public function pruneOrphans(): int
    {
        $deleted = 0;

        ContentImage::query()
            ->where('created_at', '<', now()->subHours(self::ORPHAN_AFTER_HOURS))
            ->chunkById(200, function ($images) use (&$deleted): void {
                foreach ($images as $image) {
                    /** @var ContentImage $image */
                    if ($this->isUsed($image)) {
                        continue;
                    }

                    Storage::disk(MediaUrl::DISK)->delete($image->path);
                    $image->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    private function isUsed(ContentImage $image): bool
    {
        // JSON ichida "/" qochirilgan bo'lishi mumkin — noyob fayl nomi bo'yicha qidiramiz
        $needle = '%'.basename($image->path).'%';

        return Post::withTrashed()->where('body', 'like', $needle)->exists()
            || Event::withTrashed()->where('description', 'like', $needle)->exists();
    }

    /**
     * @return array{0: string, 1: string} [ma'lumot, kengaytma]
     */
    private function encode(GdImage $image, bool $transparent): array
    {
        ob_start();

        if ($transparent) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 7);
            $extension = 'png';
        } elseif (function_exists('imagewebp')) {
            imagewebp($image, null, 82);
            $extension = 'webp';
        } else {
            imagejpeg($image, null, 85);
            $extension = 'jpg';
        }

        return [(string) ob_get_clean(), $extension];
    }
}
