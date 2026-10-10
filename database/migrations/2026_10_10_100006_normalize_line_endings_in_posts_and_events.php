<?php

use App\Models\Event;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Rasmli (multipart) forma orqali saqlangan yangilik va tadbir matnlarida qator oxiri \r\n
 * bo'lib qolgan — saytda xatboshilar ajralmasdi. Barchasi \n ga keltiriladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->normalize(Post::class, ['excerpt', 'body']);
        $this->normalize(Event::class, ['description']);
    }

    public function down(): void
    {
        // Ma'lumot migratsiyasi — qaytarilmaydi
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $fields
     */
    private function normalize(string $model, array $fields): void
    {
        $model::query()->withoutGlobalScopes()->each(function (Model $record) use ($fields): void {
            $changed = false;

            foreach ($fields as $field) {
                $raw = $record->getAttributes()[$field] ?? null;

                if (is_string($raw) && str_contains($raw, '\r')) {
                    $record->setRawAttributes([...$record->getAttributes(), $field => str_replace(['\r\n', '\r'], '\n', $raw)]);
                    $changed = true;
                }
            }

            if ($changed) {
                $record->saveQuietly();
            }
        });
    }
};
