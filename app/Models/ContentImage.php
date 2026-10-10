<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Matn muharriri orqali yuklangan rasm (App\Services\Content\ContentImageService).
 *
 * @property int $id
 * @property string $path public diskdagi yo'l: content-images/2026/10/abc.webp
 * @property int|null $user_id
 * @property int $width
 * @property int $height
 * @property int $size
 * @property Carbon|null $created_at
 */
#[Fillable(['path', 'user_id', 'width', 'height', 'size', 'created_at'])]
class ContentImage extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
