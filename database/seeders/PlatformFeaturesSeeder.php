<?php

namespace Database\Seeders;

use App\Models\ApiClient;
use App\Models\ArchivedRecord;
use App\Models\BehaviorPoint;
use App\Models\Certificate;
use App\Models\DataMaskingRule;
use App\Models\DocumentSignature;
use App\Models\Exam;
use App\Models\FeePenalty;
use App\Models\FeePenaltyRule;
use App\Models\HubMessage;
use App\Models\HubMessageDelivery;
use App\Models\OperationsAlert;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentGatewayTransaction;
use App\Models\PolicyRule;
use App\Models\RecordVersion;
use App\Models\Refund;
use App\Models\RetentionPolicy;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\SecureDocument;
use App\Models\SecureDocumentAccessLog;
use App\Models\SignableDocument;
use App\Models\StaffFeedComment;
use App\Models\StaffFeedPost;
use App\Models\StaffTask;
use App\Models\Student;
use App\Models\StudentIntervention;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;

class PlatformFeaturesSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        foreach ($this->demoSchools() as $school) {
            $admin = $this->demoAdmin($school);
            $teacherUser = $this->demoTeacherUser($school);
            $student = $this->demoStudent($school);
            $invoice = $this->demoInvoice($school);
            $payment = $this->demoPayment($school);
            $exam = $this->demoExam($school);

            if (! $admin || ! $student) {
                continue;
            }

            PolicyRule::updateOrCreate(
                ['school_id' => $school->id, 'code' => 'LATE_FEE_AUTO'],
                [
                    'name' => 'Auto Late Fee',
                    'module' => 'finance',
                    'trigger_event' => 'invoice.overdue',
                    'conditions' => ['days_overdue' => 7],
                    'actions' => ['apply_penalty' => true],
                    'priority' => 100,
                    'is_active' => true,
                ]
            );

            $hubMessage = HubMessage::updateOrCreate(
                ['school_id' => $school->id, 'subject' => 'Welcome to Term 1'],
                [
                    'sender_id' => $admin->id,
                    'body' => 'Dear parents and guardians, welcome to the new term.',
                    'channels' => ['email', 'sms'],
                    'audience_type' => 'school',
                    'audience_ids' => null,
                    'status' => 'sent',
                    'sent_at' => now()->subDays(2),
                    'delivery_stats' => ['sent' => 45, 'delivered' => 43],
                ]
            );

            HubMessageDelivery::updateOrCreate(
                [
                    'hub_message_id' => $hubMessage->id,
                    'recipient_user_id' => $this->demoParentUser($school)?->id,
                    'channel' => 'email',
                ],
                [
                    'recipient_address' => $this->demoParentUser($school)?->email,
                    'status' => 'delivered',
                    'delivered_at' => now()->subDays(2),
                ]
            );

            $signable = SignableDocument::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Parent Consent Form 2026'],
                [
                    'document_type' => 'consent',
                    'content' => 'I agree to the school terms and conditions for 2026.',
                    'status' => 'completed',
                    'created_by' => $admin->id,
                    'completed_at' => now()->subDays(5),
                ]
            );

            DocumentSignature::updateOrCreate(
                ['document_id' => $signable->id, 'signer_id' => $admin->id],
                [
                    'signer_role' => 'admin',
                    'signature_hash' => hash('sha256', 'demo-signature-' . $school->id),
                    'ip_address' => '127.0.0.1',
                    'signed_at' => now()->subDays(5),
                ]
            );

            Certificate::updateOrCreate(
                ['school_id' => $school->id, 'verification_code' => strtoupper($school->code) . '-CERT-001'],
                [
                    'student_id' => $student->id,
                    'certificate_type' => 'completion',
                    'title' => 'Term 1 Completion Certificate',
                    'content_html' => '<p>This certifies that the student completed Term 1 requirements.</p>',
                    'metadata' => ['term' => 'Term 1'],
                    'issued_by' => $admin->id,
                    'issued_at' => now()->subWeek(),
                ]
            );

            $scholarship = Scholarship::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Academic Excellence Award'],
                [
                    'type' => 'merit',
                    'amount' => 200,
                    'percentage' => null,
                    'criteria' => 'Top 5% academic performance.',
                    'application_deadline' => now()->addMonths(1)->toDateString(),
                    'slots' => 3,
                    'status' => 'open',
                ]
            );

            ScholarshipApplication::updateOrCreate(
                ['scholarship_id' => $scholarship->id, 'student_id' => $student->id],
                [
                    'school_id' => $school->id,
                    'motivation' => 'Strong academic record and leadership in class.',
                    'supporting_data' => ['average' => 82],
                    'status' => 'pending',
                ]
            );

            $gateway = PaymentGatewayConfig::updateOrCreate(
                ['school_id' => $school->id, 'provider' => 'paynow'],
                [
                    'credentials' => ['integration_id' => 'demo', 'integration_key' => 'demo-key'],
                    'is_active' => false,
                    'supports_cards' => true,
                    'supports_mobile_money' => true,
                    'supports_bank_transfer' => false,
                ]
            );

            if ($invoice) {
                PaymentGatewayTransaction::updateOrCreate(
                    ['internal_reference' => strtoupper($school->code) . '-PGT-001'],
                    [
                        'school_id' => $school->id,
                        'config_id' => $gateway->id,
                        'invoice_id' => $invoice->id,
                        'student_id' => $student->id,
                        'provider_reference' => 'PAYNOW-DEMO-001',
                        'amount' => 100,
                        'currency' => 'USD',
                        'payment_method' => 'mobile_money',
                        'status' => 'completed',
                        'provider_response' => ['poll_url' => 'https://demo.paynow.co.zw/poll'],
                        'completed_at' => now()->subDays(4),
                    ]
                );
            }

            $penaltyRule = FeePenaltyRule::updateOrCreate(
                ['school_id' => $school->id, 'name' => '7-Day Late Fee'],
                [
                    'grace_days' => 7,
                    'penalty_type' => 'fixed',
                    'penalty_value' => 10,
                    'frequency' => 'once',
                    'is_active' => true,
                ]
            );

            if ($invoice) {
                FeePenalty::updateOrCreate(
                    ['school_id' => $school->id, 'rule_id' => $penaltyRule->id, 'invoice_id' => $invoice->id],
                    [
                        'student_id' => $student->id,
                        'amount' => 10,
                        'applied_on' => now()->subDays(2)->toDateString(),
                        'status' => 'applied',
                    ]
                );
            }

            if ($payment) {
                Refund::updateOrCreate(
                    ['school_id' => $school->id, 'payment_id' => $payment->id, 'student_id' => $student->id],
                    [
                        'amount' => 25,
                        'reason' => 'Duplicate payment correction.',
                        'status' => 'pending',
                        'requested_by' => $admin->id,
                        'reference' => strtoupper($school->code) . '-REF-001',
                    ]
                );
            }

            if ($exam) {
                $secureDoc = SecureDocument::updateOrCreate(
                    ['school_id' => $school->id, 'title' => 'Exam Paper - ' . $exam->name],
                    [
                        'vault_type' => 'exam_paper',
                        'encrypted_payload' => encrypt('Demo encrypted exam paper content.'),
                        'content_hash' => hash('sha256', 'demo-exam-paper'),
                        'access_roles' => ['admin', 'teacher'],
                        'uploaded_by' => $admin->id,
                        'exam_id' => $exam->id,
                    ]
                );

                SecureDocumentAccessLog::updateOrCreate(
                    [
                        'document_id' => $secureDoc->id,
                        'user_id' => $admin->id,
                        'action' => 'view',
                    ],
                    [
                        'ip_address' => '127.0.0.1',
                        'accessed_at' => now()->subDay(),
                    ]
                );
            }

            BehaviorPoint::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'recorded_on' => now()->subDays(8)->toDateString(),
                    'category' => 'leadership',
                ],
                [
                    'points' => 5,
                    'description' => 'Led class assembly presentation.',
                    'recorded_by' => $teacherUser?->id ?? $admin->id,
                ]
            );

            StudentIntervention::updateOrCreate(
                ['school_id' => $school->id, 'student_id' => $student->id, 'intervention_type' => 'academic_support'],
                [
                    'status' => 'open',
                    'summary' => 'Extra Mathematics support sessions.',
                    'action_plan' => 'Weekly tutoring on Thursdays.',
                    'assigned_to' => $teacherUser?->id,
                    'created_by' => $admin->id,
                    'start_date' => now()->subWeeks(2)->toDateString(),
                    'follow_up_date' => now()->addWeek()->toDateString(),
                ]
            );

            StaffTask::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Prepare end-of-term reports'],
                [
                    'description' => 'Compile and review student report cards.',
                    'assigned_to' => $teacherUser?->id ?? $admin->id,
                    'assigned_by' => $admin->id,
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'due_date' => now()->addWeeks(2)->toDateString(),
                    'progress_percent' => 40,
                ]
            );

            $post = StaffFeedPost::updateOrCreate(
                ['school_id' => $school->id, 'author_id' => $teacherUser?->id ?? $admin->id, 'body' => 'Great turnout at today\'s staff meeting!'],
                [
                    'attachments' => [],
                    'visibility' => 'staff',
                ]
            );

            StaffFeedComment::updateOrCreate(
                ['post_id' => $post->id, 'author_id' => $admin->id, 'body' => 'Thanks everyone for the collaboration.'],
                []
            );

            OperationsAlert::updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Low inventory stock alert'],
                [
                    'severity' => 'warning',
                    'category' => 'inventory',
                    'message' => 'Uniform shirts (Medium) are approaching reorder level.',
                    'metadata' => ['sku' => strtoupper($school->code) . '-UNI-M'],
                ]
            );

            RetentionPolicy::updateOrCreate(
                ['school_id' => $school->id, 'module' => 'attendance'],
                [
                    'retain_years' => 7,
                    'archive_action' => 'archive',
                    'is_active' => true,
                ]
            );

            ArchivedRecord::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'source_table' => 'students',
                    'source_id' => $student->id,
                ],
                [
                    'snapshot' => ['id' => $student->id, 'name' => $student->full_name, 'archived_reason' => 'demo'],
                    'archived_at' => now()->subYears(1),
                ]
            );

            DataMaskingRule::updateOrCreate(
                ['school_id' => $school->id, 'module' => 'students', 'field' => 'phone'],
                [
                    'visible_roles' => ['admin', 'teacher'],
                    'mask_pattern' => '***-***-####',
                    'is_active' => true,
                ]
            );

            ApiClient::updateOrCreate(
                ['client_id' => 'demo-client-' . strtolower($school->code)],
                [
                    'school_id' => $school->id,
                    'name' => 'Demo Integration Client',
                    'client_secret_hash' => hash('sha256', 'demo-client-secret'),
                    'scopes' => ['students.read', 'invoices.read'],
                    'is_active' => true,
                    'last_used_at' => now()->subDays(3),
                ]
            );

            RecordVersion::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'versionable_type' => Student::class,
                    'versionable_id' => $student->id,
                    'version_number' => 1,
                ],
                [
                    'snapshot' => ['full_name' => $student->full_name, 'status' => $student->status],
                    'changed_by' => $admin->id,
                ]
            );
        }
    }
}
