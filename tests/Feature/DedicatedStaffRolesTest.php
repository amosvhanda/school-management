<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PermissionService;
use Tests\TestCase;

class DedicatedStaffRolesTest extends TestCase
{
    public function test_receptionist_has_reception_capability(): void
    {
        $user = User::factory()->create(['role' => UserRole::Receptionist]);

        $capabilities = app(PermissionService::class)->resolveCapabilities($user);

        $this->assertTrue($capabilities['isStaff']);
        $this->assertTrue($capabilities['canManageReception']);
        $this->assertFalse($capabilities['canManageLibrary']);
    }

    public function test_librarian_has_library_capability(): void
    {
        $user = User::factory()->create(['role' => UserRole::Librarian]);

        $capabilities = app(PermissionService::class)->resolveCapabilities($user);

        $this->assertTrue($capabilities['canManageLibrary']);
        $this->assertFalse($capabilities['canManageTransport']);
    }

    public function test_nurse_has_health_and_student_capabilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::Nurse]);

        $capabilities = app(PermissionService::class)->resolveCapabilities($user);

        $this->assertTrue($capabilities['canManageHealth']);
        $this->assertTrue($capabilities['canManageStudents']);
        $this->assertFalse($capabilities['canManageTeachers']);
    }

    public function test_transport_manager_has_transport_capability(): void
    {
        $user = User::factory()->create(['role' => UserRole::TransportManager]);

        $capabilities = app(PermissionService::class)->resolveCapabilities($user);

        $this->assertTrue($capabilities['canManageTransport']);
        $this->assertFalse($capabilities['canManageInventory']);
    }

    public function test_hostel_manager_has_hostel_capability(): void
    {
        $user = User::factory()->create(['role' => UserRole::HostelManager]);

        $capabilities = app(PermissionService::class)->resolveCapabilities($user);

        $this->assertTrue($capabilities['canManageHostel']);
        $this->assertFalse($capabilities['canManageHealth']);
    }
}
