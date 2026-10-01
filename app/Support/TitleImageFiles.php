<?php

namespace App\Support;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * public/ ichidagi papkadan sarlavha bo'yicha rasm topish va uni
 * public disk'ka (storage/app/public) nusxalash.
 *
 * Fayl nomi = sarlavha ("O'rta Osiyo xalqlari etnologiyasi.png"). Apostrof turlari
 * (' ʻ ’ ‘ `), katta-kichik harf, ortiqcha bo'shliq va oxiridagi nuqta farqi
 * hisobga olinmaydi. Maqola va kitob rasmlarini import qilishda ishlatiladi.
 */
final class TitleImageFiles
{
    private const EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    /** @var array<string, string> normallashtirilgan nom => to'liq yo'l */
    private array $files = [];

    public function __construct(string $publicDirectory)
    {
        $directory = public_path($publicDirectory);

        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $name) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (in_array($extension, self::EXTENSIONS, true)) {
                $this->files[self::normalize(pathinfo($name, PATHINFO_FILENAME))] = $directory.DIRECTORY_SEPARATOR.$name;
            }
        }
    }

    /** Sarlavhaga mos fayl yo'li yoki null */
    public function find(string $title): ?string
    {
        return $this->files[self::normalize($title)] ?? null;
    }

    /**
     * Faylni public disk'ka nusxalaydi: {$targetDirectory}/{$basename}.{ext}
     *
     * @return string|null Saqlangan yo'l (cover_image_path uchun)
     */
    public function store(string $source, string $targetDirectory, string $basename): ?string
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $path = Storage::disk(MediaUrl::DISK)->putFileAs($targetDirectory, new File($source), "{$basename}.{$extension}");

        return $path === false ? null : $path;
    }

    public static function normalize(string $value): string
    {
        $value = str_replace(['ʻ', 'ʼ', '‘', '’', '`', '´'], "'", $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return rtrim(mb_strtolower(trim($value)), '.');
    }
}
