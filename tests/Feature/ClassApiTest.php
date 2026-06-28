<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ClassModel;

class ClassApiTest extends TestCase
{
    public function test_get_classes_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/classes');

        $response->assertStatus(200);
    }

    public function test_create_class(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/classes', [
            'name' => 'Form 1A',
            'level' => 'Form 1',
            'capacity' => 30,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name'],
                'message',
            ]);
    }

    public function test_get_class_by_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/classes/{$class->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name']]);
    }

    public function test_update_class(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/v1/classes/{$class->id}", [
            'name' => 'Updated Class Name',
            'capacity' => 35,
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_class(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertStatus(200);
    }
}
