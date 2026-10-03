<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\UpdateAiSettingsRequest;
use App\Http\Requests\Ai\UpdatePromptTemplateRequest;
use App\Models\PromptTemplate;
use App\Models\User;
use App\Services\Ai\AiSettings;
use App\Services\Ai\PromptLibrary;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * AI Studio → Sozlamalar (faqat ai_settings.manage — Super Admin):
 * API kaliti (shifrlangan), model, limitlar, prompt shablonlari, shaxsiy limitlar.
 */
class AiSettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function update(UpdateAiSettingsRequest $request, AiSettings $settings): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $settings->update([
            'enabled' => $request->boolean('enabled'),
            'api_key' => $request->filled('api_key') ? $request->string('api_key')->toString() : null,
            'model' => $request->string('model')->toString(),
            'author_monthly_limit' => $request->integer('author_monthly_limit'),
            'staff_monthly_limit' => $request->integer('staff_monthly_limit'),
            'max_input_chars' => $request->integer('max_input_chars'),
        ], $user);

        // Kalitning o'zi jurnalga yozilmaydi — faqat almashtirilgani
        $this->audit->log(AuditEvent::AiSettingsUpdated, null, [
            'enabled' => $request->boolean('enabled'),
            'model' => $request->string('model')->toString(),
            'author_monthly_limit' => $request->integer('author_monthly_limit'),
            'staff_monthly_limit' => $request->integer('staff_monthly_limit'),
            'max_input_chars' => $request->integer('max_input_chars'),
            'api_key_changed' => $request->filled('api_key'),
        ], actor: $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI sozlamalari saqlandi.')]);

        return back();
    }

    public function destroyKey(AiSettings $settings): RedirectResponse
    {
        $settings->forgetApiKey();
        $this->audit->log(AuditEvent::AiSettingsUpdated, null, ['api_key_removed' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Bazadagi API kaliti o'chirildi (.env dagi kalit ishlatiladi).")]);

        return back();
    }

    public function updatePrompt(UpdatePromptTemplateRequest $request, PromptTemplate $promptTemplate): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $promptTemplate->forceFill([
            'system_prompt' => $request->string('system_prompt')->toString(),
            'user_prompt_template' => $request->filled('user_prompt_template') ? $request->string('user_prompt_template')->toString() : null,
            'model' => $request->filled('model') ? $request->string('model')->toString() : null,
            'temperature' => $request->float('temperature'),
            'max_tokens' => $request->integer('max_tokens'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => $user->id,
        ])->save();

        $this->audit->log(AuditEvent::AiPromptUpdated, $promptTemplate, [
            'key' => $promptTemplate->key,
            'is_active' => $promptTemplate->is_active,
            'max_tokens' => $promptTemplate->max_tokens,
        ], actor: $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('«:name» shabloni saqlandi.', ['name' => $promptTemplate->name])]);

        return back();
    }

    public function resetPrompt(Request $request, PromptTemplate $promptTemplate, PromptLibrary $prompts): RedirectResponse
    {
        abort_unless(PromptLibrary::exists($promptTemplate->key), 404);

        $template = $prompts->reset($promptTemplate->key);
        $template->forceFill(['updated_by' => $request->user()?->id])->save();
        $this->audit->log(AuditEvent::AiPromptUpdated, $template, ['key' => $template->key, 'reset' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shablon standart holatga qaytarildi.')]);

        return back();
    }

    public function updateLimit(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        $limit = $validated['limit'] ?? null;
        $user->forceFill(['ai_monthly_token_limit' => is_numeric($limit) ? (int) $limit : null])->save();
        $this->audit->log(AuditEvent::AiLimitUpdated, $user, ['limit' => $user->ai_monthly_token_limit]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name uchun limit saqlandi.', ['name' => $user->name])]);

        return back();
    }
}
