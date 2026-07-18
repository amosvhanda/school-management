<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use Tests\TestCase;

class ClassApiTest extends TestCase
{
    public function test_get_classes_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $otherClass = ClassModel::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/classes?all=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $class->id)
            ->assertJsonPath('data.0.school_id', $auth['school']->id);

        $this->assertNotContains($otherClass->id, array_column($response->json('data'), 'id'));
    }

    public function test_create_class(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/classes', [
            'name' => 'Form 1A',
            'form' => 'Form 1',
            'capacity' => 30,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'form', 'school_id'],
                'message',
            ]);

        $classId = $response->json('data.id');

        $this->assertDatabaseHas('classes', [
            'id' => $classId,
            'name' => 'Form 1A',
            'form' => 'Form 1',
            'capacity' => 30,
            'status' => 'active',
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_get_class_by_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/classes/{$class->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'school_id']])
            ->assertJsonPath('data.id', $class->id)
            ->assertJsonPath('data.school_id', $auth['school']->id);
    }

    public function test_cannot_get_class_from_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/classes/{$class->id}")
            ->assertNotFound();
    }

    public function test_update_class(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/classes/{$class->id}", [
            'name' => 'Updated Class Name',
            'capacity' => 35,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $class->id)
            ->assertJsonPath('data.name', 'Updated Class Name')
            ->assertJsonPath('data.capacity', 35);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'name' => 'Updated Class Name',
            'capacity' => 35,
        ]);
    }

    public function test_delete_class(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Class deleted successfully');

        $this->assertDatabaseMissing('classes', [
            'id' => $class->id,
        ]);
    }
}
