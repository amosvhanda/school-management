<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPhotoUploadTest extends TestCase
{
    public function test_staff_can_upload_student_photo(): void
    {
        Storage::fake('public');
        $auth = $this->createAuthenticatedUser();

        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'photo_url' => null,
        ]);

        $file = UploadedFile::fake()->image('student.jpg', 300, 300);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/students/{$student->id}/photo", [
                'file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['photo_url', 'student']]);

        $photoUrl = $response->json('data.photo_url');
        $this->assertNotEmpty($photoUrl);

        $student->refresh();
        $this->assertSame($photoUrl, $student->photo_url);
    }

    public function test_id_card_prefers_student_photo_over_user_avatar(): void
    {
        $auth = $this->createAuthenticatedUser();

        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'photo_url' => 'https://cdn.example.test/students/tariro.jpg',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/id-card/print")
            ->assertOk()
            ->assertJsonPath('data.student.photo_url', 'https://cdn.example.test/students/tariro.jpg');
    }

    public function test_staff_cannot_upload_photo_for_other_school_student(): void
    {
        Storage::fake('public');
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/students/{$student->id}/photo", [
                'file' => UploadedFile::fake()->image('student.jpg'),
            ])
            ->assertNotFound();
    }
}
