<?php

namespace Tests\Feature;

use App\Models\HelpDeskTicket;
use App\Models\School;
use App\Models\Student;
use Tests\TestCase;

class HelpDeskTest extends TestCase
{
    public function test_staff_can_create_and_list_help_desk_ticket(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $create = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/help-desk/tickets', [
                'subject' => 'Broken projector',
                'description' => 'Room 4B projector will not turn on.',
                'category' => 'facilities',
                'priority' => 'high',
                'requester_name' => 'Jane Parent',
                'requester_phone' => '+263771234567',
                'student_id' => $student->id,
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.subject', 'Broken projector')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'high');

        $ticketId = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/help-desk/tickets?status=open')
            ->assertOk()
            ->assertJsonFragment(['id' => $ticketId, 'subject' => 'Broken projector']);

        $this->assertDatabaseHas('help_desk_tickets', [
            'id' => $ticketId,
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'status' => 'open',
        ]);
    }

    public function test_staff_can_resolve_and_close_ticket(): void
    {
        $auth = $this->createAuthenticatedUser();

        $ticket = HelpDeskTicket::create([
            'school_id' => $auth['school']->id,
            'ticket_number' => 'TKT-2026-00001',
            'subject' => 'Wi-Fi issue',
            'requester_name' => 'Staff member',
            'status' => 'open',
            'created_by' => $auth['user']->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/help-desk/tickets/{$ticket->id}/resolve")
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/help-desk/tickets/{$ticket->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('help_desk_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }

    public function test_staff_cannot_access_other_school_ticket(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();

        $ticket = HelpDeskTicket::create([
            'school_id' => $otherSchool->id,
            'ticket_number' => 'TKT-2026-99999',
            'subject' => 'Other school ticket',
            'requester_name' => 'Someone',
            'status' => 'open',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/help-desk/tickets/{$ticket->id}", ['subject' => 'Hacked'])
            ->assertNotFound();
    }
}
