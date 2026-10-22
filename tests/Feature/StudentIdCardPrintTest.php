<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Services\SchoolSettingsService;
use Tests\TestCase;

class StudentIdCardPrintTest extends TestCase
{
    public function test_staff_can_fetch_printable_student_id_card_payload(): void
    {
        $auth = $this->createAuthenticatedUser();
        $school = $auth['school'];
        app(SchoolSettingsService::class)->seedDefaults($school);
        app(SchoolSettingsService::class)->set($school, 'branding', 'school_name', 'Example Academy');
        app(SchoolSettingsService::class)->set($school, 'branding', 'primary_color', '#0055AA');

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'full_name' => 'Tariro Moyo',
            'student_number' => 'STU-1001',
            'class' => 'Form 2A',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/id-card/print")
            ->assertOk()
            ->assertJsonPath('data.student.full_name', 'Tariro Moyo')
            ->assertJsonPath('data.student.student_number', 'STU-1001')
            ->assertJsonPath('data.school.name', 'Example Academy')
            ->assertJsonPath('data.school.primary_color', '#0055AA')
            ->assertJsonPath('data.document_number', 'ID-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT));
    }

    public function test_staff_cannot_print_id_card_for_other_school_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/id-card/print")
            ->assertNotFound();
    }

    public function test_staff_can_bulk_print_id_cards_for_own_school_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $students = Student::factory()->count(2)->create([
            'school_id' => $auth['school']->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/students/id-cards/bulk-print', [
                'student_ids' => $students->pluck('id')->all(),
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.student.id', $students[0]->id)
            ->assertJsonPath('data.1.student.id', $students[1]->id);
    }

    public function test_staff_cannot_bulk_print_id_cards_for_other_school_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $own = Student::factory()->create(['school_id' => $auth['school']->id]);
        $other = Student::factory()->create(['school_id' => School::factory()->create()->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/students/id-cards/bulk-print', [
                'student_ids' => [$own->id, $other->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('student_ids');
    }
}
