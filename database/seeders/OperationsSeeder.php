<?php

namespace Database\Seeders;

use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\DisciplinaryRecord;
use App\Models\Guardian;
use App\Models\HolidayAttendance;
use App\Models\HolidayEnrollment;
use App\Models\HolidayProgram;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventorySale;
use App\Models\InventorySaleItem;
use App\Models\LeaveRequest;
use App\Models\NotificationQueue;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\StudentDocument;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        foreach ($this->demoSchools() as $school) {
            $admin = $this->demoAdmin($school);
            $teacherUser = $this->demoTeacherUser($school);
            $parent = $this->demoParentUser($school);
            $student = $this->demoStudent($school);
            $teacher = $this->demoTeacher($school);
            $invoice = $this->demoInvoice($school);

            if (! $admin || ! $student) {
                continue;
            }

            $customField = CustomField::query()
                ->where('school_id', $school->id)
                ->where('slug', 'previous-school')
                ->first();

            if ($customField) {
                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $customField->id,
                        'entity_type' => Student::class,
                        'entity_id' => $student->id,
                    ],
                    ['value' => 'Demo Primary School']
                );
            }

            $item = InventoryItem::updateOrCreate(
                ['school_id' => $school->id, 'sku' => strtoupper($school->code) . '-UNI-M'],
                [
                    'name' => 'School Uniform Shirt (Medium)',
                    'type' => 'uniform',
                    'size' => 'M',
                    'unit_price' => 18.00,
                    'currency' => 'USD',
                    'stock_quantity' => 25,
                    'reorder_level' => 5,
                    'billing_mode' => 'direct_sale',
                    'is_active' => true,
                    'description' => 'White school shirt, medium size.',
                ]
            );

            $sale = InventorySale::updateOrCreate(
                ['school_id' => $school->id, 'sale_number' => strtoupper($school->code) . '-SALE-001'],
                [
                    'student_id' => $student->id,
                    'total_amount' => 36.00,
                    'currency' => 'USD',
                    'payment_method' => 'student_account',
                    'status' => 'completed',
                    'invoice_id' => $invoice?->id,
                    'sold_by' => $admin->id,
                    'notes' => 'Demo uniform sale.',
                ]
            );

            InventorySaleItem::updateOrCreate(
                ['inventory_sale_id' => $sale->id, 'inventory_item_id' => $item->id],
                ['quantity' => 2, 'unit_price' => 18.00, 'line_total' => 36.00]
            );

            InventoryMovement::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'inventory_item_id' => $item->id,
                    'type' => 'sale',
                    'reference_type' => InventorySale::class,
                    'reference_id' => $sale->id,
                ],
                [
                    'quantity_change' => -2,
                    'quantity_after' => 23,
                    'notes' => 'Sold via demo inventory sale.',
                    'created_by' => $admin->id,
                ]
            );

            $program = HolidayProgram::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'April Holiday Revision ' . now()->year],
                [
                    'academic_year' => (string) now()->year,
                    'start_date' => now()->year . '-04-01',
                    'end_date' => now()->year . '-04-10',
                    'fee_amount' => 75.00,
                    'currency' => 'USD',
                    'is_active' => true,
                    'description' => 'Intensive revision classes during school holidays.',
                ]
            );

            HolidayEnrollment::updateOrCreate(
                ['holiday_program_id' => $program->id, 'student_id' => $student->id],
                [
                    'school_id' => $school->id,
                    'invoice_id' => $invoice?->id,
                    'status' => 'enrolled',
                    'enrolled_at' => now()->create(now()->year, 3, 15),
                ]
            );

            $attendanceDate = now()->create(now()->year, 4, 5)->startOfDay();

            HolidayAttendance::updateOrCreate(
                [
                    'holiday_program_id' => $program->id,
                    'student_id' => $student->id,
                    'date' => $attendanceDate,
                ],
                [
                    'school_id' => $school->id,
                    'status' => 'present',
                    'notes' => 'On time.',
                ]
            );

            if ($parent) {
                ParentNotification::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'parent_user_id' => $parent->id,
                        'type' => 'attendance_alert',
                        'title' => 'Attendance Update',
                    ],
                    [
                        'student_id' => $student->id,
                        'body' => 'Your child was marked present today.',
                        'data' => ['student_id' => $student->id],
                        'read_at' => null,
                    ]
                );

                $thread = CommunicationThread::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'parent_user_id' => $parent->id,
                        'subject' => 'Term 1 Progress Discussion',
                    ],
                    [
                        'student_id' => $student->id,
                        'staff_user_id' => $teacherUser?->id,
                        'status' => 'open',
                        'last_message_at' => now()->subDay(),
                    ]
                );

                CommunicationMessage::updateOrCreate(
                    [
                        'thread_id' => $thread->id,
                        'sender_id' => $teacherUser?->id ?? $admin->id,
                        'body' => 'Good afternoon. Your child is progressing well in Mathematics this term.',
                    ],
                    ['read_at' => null]
                );
            }

            DisciplinaryRecord::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'incident_date' => now()->subDays(20)->toDateString(),
                    'category' => 'uniform',
                ],
                [
                    'severity' => 'minor',
                    'description' => 'Incorrect uniform shoes worn.',
                    'action_taken' => 'Verbal warning issued.',
                    'recorded_by' => $teacherUser?->id,
                    'parent_notified' => true,
                    'parent_notified_at' => now()->subDays(19),
                ]
            );

            if ($teacher) {
                LeaveRequest::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'teacher_id' => $teacher->id,
                        'start_date' => now()->addWeeks(2)->toDateString(),
                    ],
                    [
                        'requested_by' => $teacherUser?->id,
                        'type' => 'annual',
                        'end_date' => now()->addWeeks(2)->addDays(2)->toDateString(),
                        'days' => 3,
                        'reason' => 'Family commitment.',
                        'status' => 'pending',
                    ]
                );
            }

            StudentDocument::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'name' => 'Birth Certificate',
                ],
                [
                    'uploaded_by' => $admin->id,
                    'type' => 'identity',
                    'path' => 'demo/documents/birth-certificate.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => 245760,
                ]
            );

            $guardian = Guardian::query()->where('school_id', $school->id)->first();

            NotificationQueue::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'type' => 'invoice_created',
                    'notifiable_type' => Student::class,
                    'notifiable_id' => $student->id,
                    'channel' => 'email',
                ],
                [
                    'guardian_id' => $guardian?->id,
                    'parent_user_id' => $parent?->id,
                    'recipient_email' => $parent?->email ?? $student->email,
                    'subject' => 'New invoice available',
                    'message' => 'A new school invoice has been generated for your child.',
                    'data' => ['invoice_id' => $invoice?->id],
                    'status' => 'sent',
                    'sent_at' => now()->subDays(1),
                    'retry_count' => 0,
                ]
            );
        }
    }
}
