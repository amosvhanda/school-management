<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenanceLog;
use App\Models\Budget;
use App\Models\ClinicVisit;
use App\Models\ConsentForm;
use App\Models\ConsentResponse;
use App\Models\Driver;
use App\Models\GoodsReceipt;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelBed;
use App\Models\HostelRoom;
use App\Models\IncidentReport;
use App\Models\Invoice;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\Policy;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\School;
use App\Models\SchoolEvent;
use App\Models\StudentMedicalProfile;
use App\Models\StudentTransportAllocation;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\Visitor;
use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionStep;
use App\Models\WorkflowInstance;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;

class ErpModulesSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        foreach ($this->demoSchools() as $school) {
            $admin = $this->demoAdmin($school);
            $teacherUser = $this->demoTeacherUser($school);
            $parent = $this->demoParentUser($school);
            $student = $this->demoStudent($school);
            $department = $this->demoDepartment($school);
            $invoice = $this->demoInvoice($school);

            if (! $admin || ! $student) {
                continue;
            }

            $definition = WorkflowDefinition::updateOrCreate(
                ['school_id' => $school->id, 'code' => 'PROCUREMENT_APPROVAL'],
                [
                    'name' => 'Procurement Approval',
                    'module' => 'procurement',
                    'description' => 'Demo approval flow for purchase requisitions.',
                    'is_active' => true,
                ]
            );

            WorkflowDefinitionStep::updateOrCreate(
                ['definition_id' => $definition->id, 'step_order' => 1],
                [
                    'name' => 'Department Head Review',
                    'approver_role' => 'admin',
                    'approver_user_id' => $admin->id,
                ]
            );

            $instance = null;
            if ($invoice) {
                $instance = WorkflowInstance::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'definition_id' => $definition->id,
                        'subject_type' => Invoice::class,
                        'subject_id' => $invoice->id,
                    ],
                    [
                        'status' => 'approved',
                        'current_step_order' => 1,
                        'initiated_by' => $admin->id,
                        'metadata' => ['source' => 'demo_seeder'],
                        'completed_at' => now(),
                    ]
                );

                WorkflowApproval::updateOrCreate(
                    [
                        'instance_id' => $instance->id,
                        'step_order' => 1,
                    ],
                    [
                        'approver_id' => $admin->id,
                        'action' => 'approved',
                        'comments' => 'Demo approval for seeded data.',
                        'acted_at' => now(),
                    ]
                );
            }

            $vendor = Vendor::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'EduSupplies Zimbabwe'],
                [
                    'contact_person' => 'Tendai Muzenda',
                    'email' => 'orders@edusupplies.demo',
                    'phone' => '+263 772 111 222',
                    'address' => 'Harare Industrial Area',
                    'status' => 'active',
                ]
            );

            $requisition = PurchaseRequisition::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Term 1 Lab Consumables'],
                [
                    'requested_by' => $admin->id,
                    'department_id' => $department?->id,
                    'description' => 'Demo requisition for science lab supplies.',
                    'estimated_cost' => 450.00,
                    'status' => 'approved',
                    'workflow_instance_id' => $instance?->id,
                ]
            );

            PurchaseRequisitionItem::updateOrCreate(
                ['requisition_id' => $requisition->id, 'description' => 'Chemistry reagents kit'],
                ['quantity' => 2, 'unit_cost' => 125.00]
            );

            GoodsReceipt::updateOrCreate(
                ['school_id' => $school->id, 'requisition_id' => $requisition->id],
                [
                    'vendor_id' => $vendor->id,
                    'received_by' => $admin->id,
                    'received_date' => now()->subDays(7)->toDateString(),
                    'notes' => 'Delivered in full.',
                    'status' => 'received',
                ]
            );

            $asset = Asset::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Science Lab Projector'],
                [
                    'asset_tag' => strtoupper($school->code) . '-PRJ-001',
                    'category' => 'equipment',
                    'purchase_date' => now()->subYear()->toDateString(),
                    'purchase_cost' => 1200.00,
                    'location' => 'Science Block',
                    'custodian_user_id' => $teacherUser?->id,
                    'status' => 'active',
                ]
            );

            AssetMaintenanceLog::updateOrCreate(
                ['asset_id' => $asset->id, 'maintenance_date' => now()->subMonths(2)->toDateString()],
                [
                    'description' => 'Annual lamp replacement and calibration.',
                    'cost' => 85.00,
                    'performed_by' => $admin->id,
                ]
            );

            AssetDisposal::updateOrCreate(
                ['asset_id' => $asset->id, 'disposed_at' => now()->addYears(5)->toDateString()],
                [
                    'reason' => 'Planned end-of-life replacement.',
                    'workflow_instance_id' => $instance?->id,
                    'approved_by' => $admin->id,
                ]
            );

            $vehicle = Vehicle::updateOrCreate(
                ['school_id' => $school->id, 'registration_number' => strtoupper(substr($school->code, 0, 3)) . '-BUS-01'],
                [
                    'make' => 'Toyota',
                    'model' => 'Coaster',
                    'capacity' => 30,
                    'status' => 'active',
                ]
            );

            $driver = Driver::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Simbarashe Dube'],
                [
                    'license_number' => 'DRV-' . $school->id . '-001',
                    'phone' => '+263 773 444 555',
                    'status' => 'active',
                ]
            );

            $route = TransportRoute::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Kuwadzana Morning Route'],
                [
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $driver->id,
                    'route_description' => 'Kuwadzana 5 → Mufakose → School',
                    'status' => 'active',
                ]
            );

            StudentTransportAllocation::updateOrCreate(
                ['student_id' => $student->id, 'route_id' => $route->id],
                [
                    'pickup_point' => 'Kuwadzana 5 Shopping Centre',
                    'status' => 'active',
                    'allocated_at' => now()->subMonths(3)->toDateString(),
                ]
            );

            $hostel = Hostel::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Boys Hostel A'],
                ['gender' => 'male', 'capacity' => 40, 'status' => 'active']
            );

            $room = HostelRoom::updateOrCreate(
                ['hostel_id' => $hostel->id, 'room_number' => 'A-101'],
                ['capacity' => 4]
            );

            $bed = HostelBed::updateOrCreate(
                ['room_id' => $room->id, 'bed_number' => '1'],
                ['status' => 'occupied']
            );

            HostelAllocation::updateOrCreate(
                ['student_id' => $student->id, 'bed_id' => $bed->id],
                [
                    'allocated_at' => now()->subMonths(2)->toDateString(),
                    'status' => 'active',
                ]
            );

            $book = LibraryBook::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'New General Mathematics Form 4'],
                [
                    'isbn' => '978-0-DEMO-' . $school->id,
                    'author' => 'ZIMSEC Panel',
                    'total_copies' => 5,
                    'available_copies' => 4,
                    'category' => 'textbook',
                ]
            );

            LibraryLoan::updateOrCreate(
                ['book_id' => $book->id, 'student_id' => $student->id, 'borrowed_at' => now()->subDays(10)->toDateString()],
                [
                    'due_at' => now()->addDays(4)->toDateString(),
                    'returned_at' => null,
                    'fine_amount' => 0,
                    'status' => 'borrowed',
                ]
            );

            Visitor::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Grace Chigova', 'purpose' => 'Parent meeting'],
                [
                    'phone' => '+263 772 999 888',
                    'host_user_id' => $teacherUser?->id,
                    'student_id' => $student->id,
                    'check_in_at' => now()->subHours(2),
                    'check_out_at' => now()->subHour(),
                    'status' => 'checked_out',
                ]
            );

            StudentMedicalProfile::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'conditions' => 'None recorded',
                    'allergies' => 'Peanuts',
                    'blood_group' => 'O+',
                    'emergency_notes' => 'Contact guardian immediately if allergic reaction occurs.',
                    'medications' => 'Antihistamine as needed',
                ]
            );

            ClinicVisit::updateOrCreate(
                ['school_id' => $school->id, 'student_id' => $student->id, 'visit_date' => now()->subDays(14)->toDateString()],
                [
                    'complaint' => 'Headache and mild fever',
                    'diagnosis' => 'Viral infection',
                    'treatment' => 'Rest and fluids; paracetamol administered.',
                    'nurse_user_id' => $admin->id,
                ]
            );

            SchoolEvent::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Annual Sports Day'],
                [
                    'type' => 'sports',
                    'starts_at' => now()->addMonths(2)->startOfDay()->addHours(8),
                    'ends_at' => now()->addMonths(2)->startOfDay()->addHours(16),
                    'location' => 'Main Sports Ground',
                    'description' => 'Inter-house athletics and team sports.',
                    'status' => 'scheduled',
                    'created_by' => $admin->id,
                ]
            );

            Policy::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Student Code of Conduct'],
                [
                    'category' => 'discipline',
                    'content' => 'Demo policy outlining expected student behaviour and disciplinary procedures.',
                    'effective_date' => now()->subYear()->toDateString(),
                    'review_date' => now()->addMonths(6)->toDateString(),
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]
            );

            IncidentReport::updateOrCreate(
                ['school_id' => $school->id, 'category' => 'facility', 'description' => 'Broken window in Block C classroom.'],
                [
                    'reported_by' => $admin->id,
                    'severity' => 'medium',
                    'status' => 'open',
                    'workflow_instance_id' => $instance?->id,
                ]
            );

            $consentForm = ConsentForm::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Sports Day Participation Consent'],
                [
                    'content' => 'I consent for my child to participate in school sports activities.',
                    'target_audience' => 'parents',
                    'due_date' => now()->addWeeks(2)->toDateString(),
                    'requires_signature' => true,
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]
            );

            if ($parent) {
                ConsentResponse::updateOrCreate(
                    [
                        'form_id' => $consentForm->id,
                        'parent_user_id' => $parent->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => 'approved',
                        'responded_at' => now()->subDays(3),
                        'notes' => 'Approved via demo seeder.',
                    ]
                );
            }

            // Second form left unanswered so parents can test the consent workflow.
            ConsentForm::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'School Trip Photo & Media Consent'],
                [
                    'content' => 'I consent for the school to take and use photos/videos of my child during school trips and events for educational and promotional purposes.',
                    'target_audience' => 'parents',
                    'due_date' => now()->addWeeks(3)->toDateString(),
                    'requires_signature' => true,
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]
            );

            ConsentForm::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'After-School Club Participation'],
                [
                    'content' => 'I consent for my child to join after-school clubs and activities for this term.',
                    'target_audience' => 'parents',
                    'due_date' => now()->addDays(10)->toDateString(),
                    'requires_signature' => false,
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]
            );

            Budget::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Operations Budget ' . now()->year, 'fiscal_year' => (string) now()->year],
                [
                    'department' => $department?->name ?? 'General',
                    'allocated_amount' => 50000.00,
                    'spent_amount' => 12500.00,
                    'currency' => 'USD',
                ]
            );
        }
    }
}
