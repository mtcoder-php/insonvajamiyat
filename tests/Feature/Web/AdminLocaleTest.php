<?php

namespace Tests\Feature\Web;

use App\Enums\AuditEvent;
use App\Enums\PermissionName;
use App\Support\ReportPeriod;
use App\Support\Validation\AttributeNames;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Admin panel tili almashtirilganda serverdan keladigan yorliqlar ham to'liq o'sha tilda bo'lishi.
 */
class AdminLocaleTest extends TestCase
{
    public function test_backend_labels_follow_selected_locale(): void
    {
        App::setLocale('ru');

        $this->assertSame('Вошёл(а) в систему', AuditEvent::Login->label());
        $this->assertSame('Финансы', PermissionName::groups()['finance']);
        $this->assertSame('Янв', ReportPeriod::make('2026-01-01', '2026-03-31')->buckets()[0]['label']);

        App::setLocale('uz');

        $this->assertSame('Tizimga kirdi', AuditEvent::Login->label());
        $this->assertSame('Yan', ReportPeriod::make('2026-01-01', '2026-03-31')->buckets()[0]['label']);
    }

    public function test_validation_attribute_names_are_translated(): void
    {
        App::setLocale('ru');

        $this->assertSame('дата начала', AttributeNames::name('boshlanish sanasi'));

        $validator = Validator::make(['starts_at' => null], ['starts_at' => 'required'], [], ['starts_at' => 'boshlanish sanasi']);

        $this->assertStringContainsString('дата начала', (string) $validator->errors()->first('starts_at'));
    }
}
