<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit (71): tez-tez ishlatiladigan so'rovlar uchun indekslar.
 *
 *  - articles(status, published_at): saytdagi barcha ro'yxatlar — published() + latest('published_at')
 *  - articles(submitted_at): hisobotlar va statistika (whereBetween)
 *  - notifications(notifiable_type, notifiable_id, read_at): qo'ng'iroqcha — o'qilmaganlar (har 30 soniyada)
 *  - ai_requests(created_at): admin sidebar va AI statistika
 *  - payments(status, paid_at): daromad hisobotlari
 */
return new class extends Migration
{
    /** @var array<string, array<string, list<string>>> jadval => [indeks nomi => ustunlar] */
    private const INDEXES = [
        'articles' => [
            'articles_status_published_at_index' => ['status', 'published_at'],
            'articles_submitted_at_index' => ['submitted_at'],
        ],
        'notifications' => [
            'notifications_notifiable_read_at_index' => ['notifiable_type', 'notifiable_id', 'read_at'],
        ],
        'ai_requests' => [
            'ai_requests_created_at_index' => ['created_at'],
        ],
        'payments' => [
            'payments_status_paid_at_index' => ['status', 'paid_at'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (! Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
