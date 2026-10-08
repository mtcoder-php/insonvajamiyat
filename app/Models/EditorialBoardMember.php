<?php

namespace App\Models;

use App\Enums\EditorialBoardRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Tahririyat kengashi a'zosi ("Jurnal haqida" sahifasi). Tizim foydalanuvchisi bo'lishi shart emas.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $full_name
 * @property EditorialBoardRole $role
 * @property string|null $position
 * @property string|null $organization
 * @property string|null $academic_degree
 * @property string|null $country
 * @property string|null $email
 * @property string|null $orcid
 * @property string|null $photo_path
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['full_name', 'role', 'position', 'organization', 'academic_degree', 'country', 'email', 'orcid', 'photo_path', 'sort_order', 'is_active'])]
class EditorialBoardMember extends Model
{
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['full_name', 'position', 'organization', 'academic_degree'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => EditorialBoardRole::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Saytda ko'rinadigan tartib: avval rol (bosh muharrir → a'zolar), keyin sort_order.
     *
     * @param  Builder<EditorialBoardMember>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByRaw(
            "case role when 'chief_editor' then 0 when 'deputy_chief_editor' then 1 when 'executive_secretary' then 2 else 3 end",
        )->orderBy('sort_order')->orderBy('id');
    }
}
