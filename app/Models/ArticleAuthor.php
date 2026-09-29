<?php

namespace App\Models;

use Database\Factories\ArticleAuthorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Maqola muallifi (hammualliflar ham). Tizim foydalanuvchisi bo'lmasligi mumkin.
 *
 * @property int $id
 * @property int $article_id
 * @property int|null $user_id
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $email
 * @property string|null $organization
 * @property string|null $position
 * @property string|null $academic_degree
 * @property string|null $orcid
 * @property string|null $country
 * @property bool $is_corresponding
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read string $short_name
 * @property-read Article $article
 * @property-read User|null $user
 */
#[Fillable([
    'user_id', 'last_name', 'first_name', 'middle_name', 'email', 'organization',
    'position', 'academic_degree', 'orcid', 'country', 'is_corresponding', 'sort_order',
])]
class ArticleAuthor extends Model
{
    /** @use HasFactory<ArticleAuthorFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_corresponding' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * "Karimov Anvar Olimovich"
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim(implode(' ', array_filter([
            $this->last_name, $this->first_name, $this->middle_name,
        ]))));
    }

    /**
     * Kartochkalar uchun qisqa shakl: "A. Karimov"
     *
     * @return Attribute<string, never>
     */
    protected function shortName(): Attribute
    {
        return Attribute::get(fn (): string => trim(
            Str::upper(Str::substr($this->first_name, 0, 1)).'. '.$this->last_name
        ));
    }
}
