<?php

use App\Models\Event;
use App\Models\Post;
use App\Support\Html\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Yangilik matni va tadbir tavsifi endi matn muharririda (HTML) yoziladi. Eski oddiy matnlar
 * <p>…</p> xatboshilariga aylantiriladi — muharrirda ham, saytda ham bir xil ko'rinadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->convert(Post::class, 'body');
        $this->convert(Event::class, 'description');
    }

    public function down(): void
    {
        // Ma'lumot migratsiyasi — qaytarilmaydi (HTML saytda to'g'ri ko'rsatiladi)
    }

    /**
     * @param  class-string<Post|Event>  $model
     */
    private function convert(string $model, string $field): void
    {
        $model::query()->withoutGlobalScopes()->each(function (Model $record) use ($field): void {
            /** @var Post|Event $record */
            $translations = $record->getTranslations($field);
            $converted = array_filter(array_map(
                fn (mixed $value): string => is_string($value) ? RichText::toHtml($value) : '',
                $translations,
            ));

            if ($converted !== $translations) {
                $record->replaceTranslations($field, $converted);
                $record->saveQuietly();
            }
        });
    }
};
