<?php

namespace Tests\Feature\Api\V1;

use App\Models\CustomField;
use App\Models\School;
use App\Services\SchoolSettingsService;
use App\Services\TerminologyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_registration_seeds_default_settings(): void
    {
        $response = $this->postJson('/api/v1/schools/register', [
            'school_name' => 'Test Academy',
            'school_code' => 'TEST001',
            'admin_name' => 'Admin User',
            'admin_email' => 'admin@testacademy.com',
            'admin_password' => 'Password123',
            'admin_password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201);

        $school = School::where('code', 'TEST001')->first();
        $settings = app(SchoolSettingsService::class)->getAll($school);

        $this->assertArrayHasKey('regional', $settings);
        $this->assertArrayHasKey('academic', $settings);
        $this->assertEquals('USD', $settings['regional']['currency']);
    }

    public function test_setup_prefs_persist_for_class_exam_and_grading_panes(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(SchoolSettingsService::class)->seedDefaults($auth['school']);

        $payload = [
            ['group' => 'prefs', 'key' => 'class_settings_enabled', 'value' => false],
            ['group' => 'prefs', 'key' => 'class_settings_contact', 'value' => 'Ms Class Lead'],
            ['group' => 'prefs', 'key' => 'class_settings_notes', 'value' => 'Internal note'],
            ['group' => 'prefs', 'key' => 'exam_settings_support_email', 'value' => 'exams@test.school'],
            ['group' => 'prefs', 'key' => 'grading_rules_require_approval', 'value' => true],
        ];

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson('/api/v1/settings/school', ['settings' => $payload])
            ->assertOk()
            ->assertJsonPath('data.prefs.class_settings_enabled', false)
            ->assertJsonPath('data.prefs.class_settings_contact', 'Ms Class Lead')
            ->assertJsonPath('data.prefs.exam_settings_support_email', 'exams@test.school')
            ->assertJsonPath('data.prefs.grading_rules_require_approval', true);

        $stored = app(SchoolSettingsService::class)->getAll($auth['school']->fresh());
        $this->assertSame('Internal note', $stored['prefs']['class_settings_notes']);
    }

    public function test_school_update_syncs_academic_settings_in_database(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(SchoolSettingsService::class)->seedDefaults($auth['school']);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson('/api/v1/school', [
                'academic_year' => '2026 / 2027',
                'current_term' => 'Term 3',
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.academic_year', '2026 / 2027')
            ->assertJsonPath('data.current_term', 'Term 3');

        $settings = app(SchoolSettingsService::class)->getAll($auth['school']->fresh());
        $this->assertSame('2026 / 2027', $settings['academic']['academic_year']);
        $this->assertSame('Term 3', $settings['academic']['current_term']);
    }

    public function test_admin_can_update_school_settings(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(SchoolSettingsService::class)->seedDefaults($auth['school']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson('/api/v1/settings/school', [
            'settings' => [
                [
                    'group' => 'regional',
                    'key' => 'currency',
                    'value' => 'ZWG',
                ],
                [
                    'group' => 'regional',
                    'key' => 'date_format',
                    'value' => 'd/m/Y',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.regional.currency', 'ZWG')
            ->assertJsonPath('data.regional.date_format', 'd/m/Y');

        $this->assertSame('ZWG', $auth['school']->fresh()->getDefaultCurrency());
    }

    public function test_school_profile_update_sets_fees_currency(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson('/api/v1/school', [
            'currency' => 'ZWG',
        ])
            ->assertOk()
            ->assertJsonPath('data.currency', 'ZWG');

        $this->assertSame('ZWG', $auth['school']->fresh()->getDefaultCurrency());
        $this->assertSame(
            'ZWG',
            app(SchoolSettingsService::class)->get($auth['school']->fresh(), 'regional', 'currency')
        );
    }

    public function test_fees_currency_cannot_change_after_payments(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = \App\Models\Student::factory()->create([
            'school_id' => $auth['school']->id,
            'currency' => 'USD',
            'balance' => 0,
        ]);

        \App\Models\Payment::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 10,
            'currency' => 'USD',
            'method' => 'cash',
            'status' => 'completed',
            'date' => now()->toDateString(),
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson('/api/v1/school', [
            'currency' => 'ZWG',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);

        $this->assertSame('USD', $auth['school']->fresh()->getDefaultCurrency());
    }

    public function test_public_config_includes_terminology(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(SchoolSettingsService::class)->seedDefaults($auth['school']);
        app(TerminologyService::class)->seedDefaults($auth['school']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/settings/config');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['settings', 'terminology'],
            ]);
    }

    public function test_admin_can_customize_terminology(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(TerminologyService::class)->seedDefaults($auth['school']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson('/api/v1/settings/terminology', [
            'locale' => 'en',
            'mappings' => [
                'class' => 'Grade',
                'classes' => 'Grades',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.mappings.class', 'Grade')
            ->assertJsonPath('data.mappings.classes', 'Grades');
    }

    public function test_admin_can_create_custom_field_for_students(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/settings/custom-fields', [
            'entity_type' => CustomField::ENTITY_STUDENT,
            'name' => 'Blood Type',
            'field_type' => 'select',
            'options' => ['A', 'B', 'AB', 'O'],
            'is_required' => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'blood-type')
            ->assertJsonPath('data.entity_type', 'student');
    }

    public function test_custom_fields_index_returns_all_entity_types_by_default(): void
    {
        $auth = $this->createAuthenticatedUser();
        $schoolId = $auth['school']->id;

        CustomField::withoutGlobalScopes()->create([
            'school_id' => $schoolId,
            'entity_type' => CustomField::ENTITY_STUDENT,
            'name' => 'Allergy notes',
            'slug' => 'allergy-notes',
            'field_type' => 'text',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        CustomField::withoutGlobalScopes()->create([
            'school_id' => $schoolId,
            'entity_type' => CustomField::ENTITY_TEACHER,
            'name' => 'Qualification',
            'slug' => 'qualification',
            'field_type' => 'text',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $all = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/settings/custom-fields')
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $all);

        $teachersOnly = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/settings/custom-fields?entity_type=teacher')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $teachersOnly);
        $this->assertSame('teacher', $teachersOnly[0]['entity_type']);
    }
}
