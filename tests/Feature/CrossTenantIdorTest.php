<?php

namespace Tests\Feature;

use App\Models\Hostel;
use App\Models\HostelBed;
use App\Models\HostelRoom;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use Tests\TestCase;

class CrossTenantIdorTest extends TestCase
{
    public function test_cannot_show_or_print_invoice_from_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);
        $otherInvoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'currency' => $otherSchool->currency_default,
        ]);

        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)
            ->getJson("/api/v1/invoices/{$otherInvoice->id}")
            ->assertNotFound();

        $this->withHeaders($headers)
            ->getJson("/api/v1/invoices/{$otherInvoice->id}/print")
            ->assertNotFound();

        $this->withHeaders($headers)
            ->putJson("/api/v1/invoices/{$otherInvoice->id}", [
                'description' => 'Hijacked',
            ])
            ->assertNotFound();
    }

    public function test_cannot_receipt_reverse_or_delete_payment_from_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);
        $otherInvoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'currency' => $otherSchool->currency_default,
        ]);
        $otherPayment = Payment::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'invoice_id' => $otherInvoice->id,
            'currency' => $otherSchool->currency_default,
            'method' => 'cash',
            'status' => 'pending',
        ]);

        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)
            ->getJson("/api/v1/payments/{$otherPayment->id}/receipt")
            ->assertNotFound();

        $this->withHeaders($headers)
            ->postJson("/api/v1/payments/{$otherPayment->id}/reverse")
            ->assertNotFound();

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/payments/{$otherPayment->id}")
            ->assertNotFound();
    }

    public function test_cannot_allocate_hostel_bed_or_student_from_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();

        $ownStudent = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);
        $otherStudent = Student::factory()->create([
            'school_id' => $otherSchool->id,
            'status' => 'active',
        ]);

        $ownHostel = Hostel::query()->create([
            'school_id' => $auth['school']->id,
            'name' => 'Own Hostel',
            'status' => 'active',
        ]);
        $ownRoom = HostelRoom::query()->create([
            'hostel_id' => $ownHostel->id,
            'room_number' => 'A1',
            'capacity' => 2,
        ]);
        $ownBed = HostelBed::query()->create([
            'room_id' => $ownRoom->id,
            'bed_number' => '1',
            'status' => 'available',
        ]);

        $otherHostel = Hostel::query()->withoutSchoolScope()->create([
            'school_id' => $otherSchool->id,
            'name' => 'Other Hostel',
            'status' => 'active',
        ]);
        $otherRoom = HostelRoom::query()->withoutGlobalScopes()->create([
            'hostel_id' => $otherHostel->id,
            'room_number' => 'B1',
            'capacity' => 2,
        ]);
        $otherBed = HostelBed::query()->withoutGlobalScopes()->create([
            'room_id' => $otherRoom->id,
            'bed_number' => '1',
            'status' => 'available',
        ]);

        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)
            ->postJson('/api/v1/hostels/allocations', [
                'student_id' => $ownStudent->id,
                'bed_id' => $otherBed->id,
            ])
            ->assertNotFound();

        $this->withHeaders($headers)
            ->postJson('/api/v1/hostels/allocations', [
                'student_id' => $otherStudent->id,
                'bed_id' => $ownBed->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('hostel_allocations', [
            'student_id' => $ownStudent->id,
            'bed_id' => $otherBed->id,
        ]);
        $this->assertDatabaseMissing('hostel_allocations', [
            'student_id' => $otherStudent->id,
            'bed_id' => $ownBed->id,
        ]);
    }

    public function test_cannot_download_results_for_student_from_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->get("/api/v1/students/{$otherStudent->id}/results/download?format=html")
            ->assertNotFound();
    }

    public function test_teacher_cannot_view_invoice_via_policy(): void
    {
        $auth = $this->createAuthenticatedUser('teacher');
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'currency' => $auth['school']->currency_default,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertForbidden();
    }
}
