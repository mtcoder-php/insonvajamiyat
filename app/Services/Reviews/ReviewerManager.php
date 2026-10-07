<?php

namespace App\Services\Reviews;

use App\Enums\AuditEvent;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Subject;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Taqrizchilar bazasini boshqarish (muharrirlar uchun, articles.assign_reviewer):
 * mavjud foydalanuvchiga "Taqrizchi" rolini berish / olish, vaqtincha to'xtatish
 * va ilmiy yo'nalishlarini belgilash. Boshqa rollarga tegilmaydi.
 */
class ReviewerManager
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<int, int>  $subjectIds
     */
    public function add(User $user, array $subjectIds, User $actor): void
    {
        if ($user->is_blocked || $user->trashed()) {
            throw ValidationException::withMessages([
                'user_id' => __("Bloklangan yoki o'chirilgan foydalanuvchini taqrizchi qilib bo'lmaydi."),
            ]);
        }

        if ($user->hasRole(RoleName::Reviewer)) {
            throw ValidationException::withMessages([
                'user_id' => __(':name allaqachon taqrizchi.', ['name' => $user->name]),
            ]);
        }

        DB::transaction(function () use ($user, $subjectIds): void {
            $user->assignRole(RoleName::Reviewer->value);
            $user->forceFill(['reviews_paused_at' => null])->save();

            if ($subjectIds !== []) {
                $user->subjects()->syncWithoutDetaching($subjectIds);
            }
        });

        $this->audit->log(AuditEvent::ReviewerAdded, $user, ['subjects' => count($subjectIds)], actor: $actor);
    }

    /**
     * Taqrizchilikdan chiqarish: faol taqrizlari bo'lsa — mumkin emas (avval bekor qiling).
     * Yakunlangan taqrizlar tarixi saqlanadi.
     */
    public function remove(User $user, User $actor): void
    {
        $active = $user->reviews()
            ->whereIn('status', [ReviewStatus::Invited->value, ReviewStatus::Accepted->value])
            ->count();

        if ($active > 0) {
            throw ValidationException::withMessages([
                'reviewer' => __(":name da :count ta faol taqriz bor. Avval ularni yakunlang yoki bekor qiling, yoki taqrizchini vaqtincha to'xtating.", [
                    'name' => $user->name,
                    'count' => $active,
                ]),
            ]);
        }

        $user->removeRole(RoleName::Reviewer->value);
        $user->forceFill(['reviews_paused_at' => null])->save();

        $this->audit->log(AuditEvent::ReviewerRemoved, $user, [], actor: $actor);
    }

    public function setPaused(User $user, bool $paused, User $actor): void
    {
        if ($paused === ($user->reviews_paused_at !== null)) {
            return;
        }

        $user->forceFill(['reviews_paused_at' => $paused ? now() : null])->save();

        $this->audit->log($paused ? AuditEvent::ReviewerPaused : AuditEvent::ReviewerResumed, $user, [], actor: $actor);
    }

    /**
     * @param  array<int, int>  $subjectIds
     */
    public function syncSubjects(User $user, array $subjectIds, User $actor): void
    {
        $before = $user->subjects()->pluck('subjects.id')->map(fn (mixed $id): int => (int) $id)->sort()->values()->all();
        $user->subjects()->sync($subjectIds);
        $after = collect($subjectIds)->sort()->values()->all();

        if ($before === $after) {
            return;
        }

        $names = Subject::query()->whereKey($subjectIds)->get()->map(fn (Subject $s): string => $s->name)->all();

        $this->audit->log(AuditEvent::ReviewerSubjectsUpdated, $user, ['subjects' => $names], actor: $actor);
    }
}
