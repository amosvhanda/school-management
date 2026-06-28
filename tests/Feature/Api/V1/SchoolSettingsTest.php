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
}
