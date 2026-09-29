<?php

namespace App\Models;

use Database\Factories\AuthorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Muallifning ilmiy profili (users bilan 1:1).
 *
 * @property int $id
 * @property int $user_id
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $position
 * @property string|null $organization
 * @property string|null $department
 * @property string|null $academic_degree
 * @property string|null $academic_title
 * @property int|null $experience_years
 * @property string|null $orcid
 * @property string|null $country
 * @property string|null $city
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property bool $is_public
 * @property Carbon|null $onboarding_completed_at
 * @property-read string $full_name
 * @property-read string $short_name
 * @property-read User $user
 */
#[Fillable([
    'last_name', 'first_name', 'middle_name', 'position', 'organization', 'department',
    'academic_degree', 'academic_title', 'experience_years', 'orcid', 'country', 'city',
    'bio', 'avatar_path', 'is_public',
])]
class AuthorProfile extends Model
{
    /** @use HasFactory<AuthorProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'is_public' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "Karimov Muxtor Alisher o'g'li" */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim(
            implode(' ', array_filter([$this->last_name, $this->first_name, $this->middle_name]))
        ));
    }

    /** Ilmiy uslubdagi qisqa ism: "Karimov M. A." */
    protected function shortName(): Attribute
    {
        return Attribute::get(function (): string {
            $initials = collect([$this->first_name, $this->middle_name])
                ->filter()
                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)).'.')
                ->implode(' ');

            return trim($this->last_name.' '.$initials);
        });
    }

    /** users.name ni profil bilan sinxron saqlash uchun */
    public function displayName(): string
    {
        return trim($this->last_name.' '.$this->first_name);
    }
}
