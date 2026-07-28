<?php

namespace Database\Seeders;

use App\Models\AbacPolicy;
use App\Models\AcademicCalendarEntry;
use App\Models\AdmissionScore;
use App\Models\AlumniRecord;
use App\Models\AssessmentCategory;
use App\Models\Asset;
use App\Models\BankStatementLine;
use App\Models\CbtExamSession;
use App\Models\CbtResponse;
use App\Models\ChartOfAccount;
use App\Models\ComplianceRequirement;
use App\Models\ContinuousAssessment;
use App\Models\CrossSchoolTransfer;
use App\Models\CurriculumVersion;
use App\Models\EnrollmentApplication;
use App\Models\ExamAntiCheatLog;
use App\Models\ExchangeRate;
use App\Models\GradebookRule;
use App\Models\GradeLevel;
use App\Models\GroupPolicy;
use App\Models\InstalmentPlan;
use App\Models\InstalmentScheduleItem;
use App\Models\IntegrationConnector;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LearningOutcomeRecord;
use App\Models\LibraryLoan;
use App\Models\MessageCampaign;
use App\Models\PerformanceReview;
use App\Models\PromotionRule;
use App\Models\QuestionBankItem;
use App\Models\RemarkRequest;
use App\Models\RevenueRecognitionRule;
use App\Models\StaffCertification;
use App\Models\StaffContract;
use App\Models\StudentTimelineEvent;
use App\Models\Subject;
use App\Models\SubjectPrerequisite;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Models\WebhookSubscription;
use App\Models\WorkflowDelegation;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnterpriseModulesSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        $schools = $this->demoSchools();
        $primarySchool = $schools->first();
        $secondarySchool = $schools->skip(1)->first();

        foreach ($schools as $school) {
            $admin = $this->demoAdmin($school);
            $teacherUser = $this->demoTeacherUser($school);
            $teacher = $this->demoTeacher($school);
            $parent = $this->demoParentUser($school);
            $student = $this->demoStudent($school);
            $subject = $this->demoSubject($school);
            $term = $this->demoTerm($school);
            $room = $this->demoRoom($school);
            $gradeLevel = GradeLevel::query()->where('school_id', $school->id)->first();
            $invoice = $this->demoInvoice($school);
            $payment = $this->demoPayment($school);
            $exam = $this->demoExam($school);
            $examResult = $this->demoExamResult($school);
            $subjects = Subject::query()->where('school_id', $school->id)->orderBy('id')->limit(2)->get();

            if (! $admin || ! $student || ! $subject) {
                continue;
            }

            CurriculumVersion::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'academic_year' => (string) now()->year,
                    'version_number' => 1,
                ],
                [
                    'term_id' => $term?->id,
                    'syllabus' => 'Demo syllabus outline for '.$subject->name,
                    'learning_outcomes' => ['LO1' => 'Understand core concepts', 'LO2' => 'Apply knowledge'],
                    'status' => 'published',
                    'published_by' => $admin->id,
                    'published_at' => now()->subMonth(),
                ]
            );

            LearningOutcomeRecord::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'outcome_code' => 'LO1',
                ],
                [
                    'competency_level' => 'proficient',
                    'score' => 78.5,
                    'evidence' => 'Class test and assignment average.',
                    'assessed_by' => $teacherUser?->id,
                    'assessed_on' => now()->subDays(10)->toDateString(),
                ]
            );

            $category = AssessmentCategory::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Coursework', 'type' => 'continuous'],
                ['subject_id' => $subject->id, 'weight' => 30, 'is_active' => true]
            );

            ContinuousAssessment::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'category_id' => $category->id,
                    'title' => 'Assignment 1',
                ],
                [
                    'term_id' => $term?->id,
                    'score' => 82,
                    'max_score' => 100,
                    'assessed_on' => now()->subDays(12)->toDateString(),
                    'recorded_by' => $teacherUser?->id,
                ]
            );

            GradebookRule::updateOrCreate(
                ['school_id' => $school->id, 'subject_id' => $subject->id, 'grade_level_id' => $gradeLevel?->id],
                ['category_weights' => ['coursework' => 30, 'exam' => 70], 'pass_mark' => 50]
            );

            if ($subjects->count() >= 2) {
                SubjectPrerequisite::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'subject_id' => $subjects[1]->id,
                        'prerequisite_subject_id' => $subjects[0]->id,
                    ],
                    ['minimum_grade' => 50]
                );
            }

            PromotionRule::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Standard Promotion Rule'],
                [
                    'conditions' => ['min_average' => 50, 'max_failures' => 2],
                    'action' => 'promote',
                    'is_active' => true,
                ]
            );

            AcademicCalendarEntry::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'title' => 'Term 1 Opening',
                    'start_date' => now()->startOfYear()->toDateString(),
                ],
                [
                    'term_id' => $term?->id,
                    'entry_type' => 'term_start',
                    'end_date' => now()->startOfYear()->toDateString(),
                    'is_holiday' => false,
                    'metadata' => ['source' => 'demo_seeder'],
                ]
            );

            $cashAccount = ChartOfAccount::updateOrCreate(
                ['school_id' => $school->id, 'code' => '1000'],
                ['name' => 'Cash at Bank', 'account_type' => 'asset', 'normal_balance' => 'debit', 'is_active' => true]
            );

            $revenueAccount = ChartOfAccount::updateOrCreate(
                ['school_id' => $school->id, 'code' => '4000'],
                ['name' => 'Tuition Revenue', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'is_active' => true]
            );

            $journal = JournalEntry::updateOrCreate(
                ['reference' => strtoupper($school->code).'-JE-001'],
                [
                    'school_id' => $school->id,
                    'entry_date' => now()->subDays(5)->toDateString(),
                    'description' => 'Demo tuition receipt journal entry.',
                    'status' => 'posted',
                    'currency' => 'USD',
                    'created_by' => $admin->id,
                ]
            );

            JournalLine::updateOrCreate(
                ['journal_entry_id' => $journal->id, 'account_id' => $cashAccount->id, 'debit' => 500],
                ['credit' => 0, 'currency' => 'USD', 'memo' => 'Cash received']
            );

            JournalLine::updateOrCreate(
                ['journal_entry_id' => $journal->id, 'account_id' => $revenueAccount->id, 'credit' => 500],
                ['debit' => 0, 'currency' => 'USD', 'memo' => 'Tuition revenue']
            );

            ExchangeRate::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'from_currency' => 'USD',
                    'to_currency' => 'ZWL',
                    'effective_date' => $this->demoDate(1, 1),
                ],
                ['rate' => 13.50000000]
            );

            if ($invoice) {
                $plan = InstalmentPlan::updateOrCreate(
                    ['school_id' => $school->id, 'student_id' => $student->id, 'invoice_id' => $invoice->id],
                    ['total_amount' => 300, 'currency' => 'USD', 'status' => 'active']
                );

                InstalmentScheduleItem::updateOrCreate(
                    ['plan_id' => $plan->id, 'installment_number' => 1],
                    [
                        'due_date' => now()->addWeeks(2)->toDateString(),
                        'amount' => 150,
                        'amount_paid' => 0,
                        'status' => 'pending',
                    ]
                );
            }

            BankStatementLine::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'transaction_date' => now()->subDays(3)->toDateString(),
                    'reference' => strtoupper($school->code).'-BNK-001',
                ],
                [
                    'description' => 'School fees deposit',
                    'amount' => 500,
                    'currency' => 'USD',
                    'matched_payment_id' => $payment?->id,
                    'match_status' => $payment ? 'matched' : 'unmatched',
                ]
            );

            RevenueRecognitionRule::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Term-based Recognition'],
                [
                    'recognition_method' => 'term_proration',
                    'config' => ['terms_per_year' => 3],
                    'is_active' => true,
                ]
            );

            if ($teacherUser) {
                WorkflowDelegation::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'delegator_id' => $admin->id,
                        'delegate_id' => $teacherUser->id,
                        'starts_on' => $this->demoDate(1, 15),
                    ],
                    [
                        'ends_on' => $this->demoDate(1, 22),
                        'scope' => 'all_workflows',
                        'is_active' => true,
                    ]
                );

                StaffContract::updateOrCreate(
                    ['school_id' => $school->id, 'user_id' => $teacherUser->id, 'contract_type' => 'permanent'],
                    [
                        'start_date' => now()->subYears(2)->toDateString(),
                        'salary' => 850.00,
                        'status' => 'active',
                    ]
                );

                PerformanceReview::updateOrCreate(
                    ['school_id' => $school->id, 'staff_user_id' => $teacherUser->id, 'period' => (string) now()->year],
                    [
                        'reviewer_id' => $admin->id,
                        'overall_score' => 4.2,
                        'summary' => 'Consistently strong classroom delivery.',
                        'criteria_scores' => ['planning' => 4.5, 'delivery' => 4.0],
                        'status' => 'completed',
                    ]
                );

                StaffCertification::updateOrCreate(
                    ['school_id' => $school->id, 'user_id' => $teacherUser->id, 'certification_name' => 'First Aid Level 1'],
                    [
                        'issuer' => 'St John Ambulance',
                        'issued_on' => now()->subYear()->toDateString(),
                        'expires_on' => now()->addYear()->toDateString(),
                    ]
                );

                DB::table('staff_disciplinary_cases')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'staff_user_id' => $teacherUser->id,
                        'category' => 'attendance',
                    ],
                    [
                        'description' => 'Late arrival on three consecutive days.',
                        'outcome' => 'Counselled and monitoring in place.',
                        'status' => 'closed',
                        'recorded_by' => $admin->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                DB::table('staff_shifts')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'user_id' => $teacherUser->id,
                        'shift_date' => $this->demoDate(2, 1)->toDateString(),
                    ],
                    [
                        'start_time' => '07:30:00',
                        'end_time' => '15:30:00',
                        'location' => 'Main Campus',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $application = EnrollmentApplication::query()->where('school_id', $school->id)->first();

            AdmissionScore::updateOrCreate(
                ['school_id' => $school->id, 'applicant_name' => 'Demo Applicant '.$school->code],
                [
                    'enrollment_application_id' => $application?->id,
                    'academic_score' => 72,
                    'interview_score' => 68,
                    'total_score' => 70,
                    'recommendation' => 'accept',
                ]
            );

            AlumniRecord::updateOrCreate(
                ['school_id' => $school->id, 'full_name' => 'Former Student Demo'],
                [
                    'student_id' => null,
                    'graduation_year' => (string) (now()->year - 3),
                    'email' => 'alumni@demo.school.co.zw',
                    'phone' => '+263 771 000 111',
                    'current_occupation' => 'Software Engineer',
                    'engagement_history' => ['donations' => 1, 'events_attended' => 2],
                ]
            );

            StudentTimelineEvent::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'event_type' => 'enrollment',
                    'title' => 'Enrolled in current class',
                ],
                [
                    'description' => 'Student enrolled for the current academic year.',
                    'metadata' => ['class' => $student->class],
                    'occurred_at' => now()->subMonths(4),
                ]
            );

            $question = QuestionBankItem::updateOrCreate(
                ['school_id' => $school->id, 'question_text' => 'What is 12 × 8?'],
                [
                    'subject_id' => $subject->id,
                    'question_type' => 'multiple_choice',
                    'options' => ['86', '96', '106', '116'],
                    'correct_answer' => '96',
                    'difficulty' => 'easy',
                    'tags' => ['arithmetic'],
                    'marks' => 1,
                ]
            );

            if ($exam) {
                $session = CbtExamSession::updateOrCreate(
                    ['school_id' => $school->id, 'exam_id' => $exam->id, 'student_id' => $student->id],
                    [
                        'question_ids' => [$question->id],
                        'started_at' => now()->subHours(2),
                        'submitted_at' => now()->subHour(),
                        'score' => 1,
                        'status' => 'completed',
                    ]
                );

                CbtResponse::updateOrCreate(
                    ['session_id' => $session->id, 'question_id' => $question->id],
                    ['answer' => '96', 'is_correct' => true]
                );

                ExamAntiCheatLog::updateOrCreate(
                    ['session_id' => $session->id, 'event_type' => 'tab_switch'],
                    ['metadata' => ['count' => 1], 'logged_at' => now()->subHours(2)]
                );
            }

            if ($examResult) {
                RemarkRequest::updateOrCreate(
                    ['school_id' => $school->id, 'exam_result_id' => $examResult->id],
                    [
                        'student_id' => $examResult->student_id,
                        'reason' => 'Requesting remark due to suspected marking error.',
                        'status' => 'pending',
                    ]
                );
            }

            MessageCampaign::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Term 1 Newsletter'],
                [
                    'channels' => ['email', 'sms'],
                    'audience_filter' => ['roles' => ['parent']],
                    'message_body' => 'Welcome to Term 1. Here are important dates for your calendar.',
                    'scheduled_at' => now()->addDays(3),
                    'status' => 'scheduled',
                    'created_by' => $admin->id,
                ]
            );

            if ($parent) {
                DB::table('communication_preferences')->updateOrInsert(
                    ['user_id' => $parent->id],
                    [
                        'channel_preferences' => json_encode(['email' => true, 'sms' => true]),
                        'topic_preferences' => json_encode(['fees' => true, 'attendance' => true]),
                        'marketing_opt_in' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                DB::table('parent_engagement_events')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'parent_user_id' => $parent->id,
                        'title' => 'Open Day 2026',
                    ],
                    [
                        'event_type' => 'open_day',
                        'attended_at' => now()->subMonths(1),
                        'attended' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::table('maintenance_schedules')->updateOrInsert(
                ['school_id' => $school->id, 'title' => 'Generator Service'],
                [
                    'asset_type' => Asset::class,
                    'asset_id' => null,
                    'frequency' => 'quarterly',
                    'next_due_date' => now()->addMonth()->toDateString(),
                    'status' => 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('facility_tasks')->updateOrInsert(
                ['school_id' => $school->id, 'task_type' => 'cleaning', 'location' => 'Science Block'],
                [
                    'scheduled_date' => now()->addDays(2)->toDateString(),
                    'assigned_to' => $admin->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if ($room) {
                DB::table('room_utilization_logs')->updateOrInsert(
                    ['school_id' => $school->id, 'room_id' => $room->id, 'log_date' => $this->demoDate(3, 1)->toDateString()],
                    [
                        'hours_used' => 6,
                        'capacity' => 35,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $route = TransportRoute::query()->where('school_id', $school->id)->first();
            $vehicle = Vehicle::query()->where('school_id', $school->id)->first();

            if ($route) {
                DB::table('transport_route_stops')->updateOrInsert(
                    ['route_id' => $route->id, 'stop_name' => 'Kuwadzana 5'],
                    [
                        'stop_order' => 1,
                        'pickup_time' => '06:30:00',
                        'latitude' => -17.8332,
                        'longitude' => 30.9265,
                    ]
                );

                DB::table('transport_trips')->updateOrInsert(
                    ['school_id' => $school->id, 'route_id' => $route->id, 'trip_date' => $this->demoDate(3, 5)->toDateString()],
                    [
                        'vehicle_id' => $vehicle?->id,
                        'departure_time' => '06:30:00',
                        'arrival_time' => '07:15:00',
                        'status' => 'completed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            if ($vehicle) {
                DB::table('gps_tracking_logs')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'vehicle_id' => $vehicle->id,
                        'recorded_at' => $this->demoDate(3, 5)->setTime(7, 30),
                    ],
                    ['latitude' => -17.8290, 'longitude' => 30.9300, 'speed' => 35.5]
                );

                DB::table('fuel_consumption_logs')->updateOrInsert(
                    ['school_id' => $school->id, 'vehicle_id' => $vehicle->id, 'log_date' => $this->demoDate(3, 4)->toDateString()],
                    [
                        'litres' => 45.5,
                        'cost' => 68.25,
                        'odometer' => 125000,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $loan = LibraryLoan::query()->whereHas('book', fn ($q) => $q->where('school_id', $school->id))->first();

            if ($loan) {
                DB::table('library_fines')->updateOrInsert(
                    ['school_id' => $school->id, 'loan_id' => $loan->id],
                    [
                        'amount' => 2.50,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $book = $loan?->book ?? null;

            if ($book) {
                DB::table('library_reservations')->updateOrInsert(
                    ['school_id' => $school->id, 'book_id' => $book->id, 'student_id' => $student->id],
                    [
                        'user_id' => $admin->id,
                        'reserved_at' => now(),
                        'expires_at' => now()->addDays(3),
                        'status' => 'queued',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::table('digital_library_resources')->updateOrInsert(
                ['school_id' => $school->id, 'title' => 'Form 4 Mathematics eBook'],
                [
                    'resource_type' => 'ebook',
                    'file_path' => 'demo/library/form4-maths.pdf',
                    'isbn' => '978-DEMO-EBOOK',
                    'access_roles' => json_encode(['student', 'teacher']),
                    'view_count' => 12,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            ComplianceRequirement::updateOrCreate(
                ['school_id' => $school->id, 'requirement_code' => 'MOE-ANNUAL-RETURN'],
                [
                    'authority' => 'Ministry of Education',
                    'title' => 'Annual School Return',
                    'due_date' => now()->addMonths(2)->toDateString(),
                    'status' => 'pending',
                    'evidence' => [],
                ]
            );

            IntegrationConnector::updateOrCreate(
                ['school_id' => $school->id, 'provider' => 'moodle', 'connector_type' => 'lms'],
                [
                    'config' => ['base_url' => 'https://moodle.demo.school', 'enabled' => false],
                    'is_active' => false,
                ]
            );

            WebhookSubscription::updateOrCreate(
                ['school_id' => $school->id, 'event_type' => 'payment.completed'],
                [
                    'target_url' => 'https://example.com/webhooks/payments',
                    'secret' => 'demo-webhook-secret',
                    'is_active' => true,
                ]
            );

            AbacPolicy::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Finance Read Access'],
                [
                    'resource' => 'invoices',
                    'conditions' => ['roles' => ['admin', 'bursar']],
                    'effect' => 'allow',
                    'is_active' => true,
                ]
            );

            DB::table('device_sessions')->updateOrInsert(
                ['user_id' => $admin->id, 'device_name' => 'Admin MacBook'],
                [
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Demo Browser',
                    'last_active_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if ($teacher && $secondarySchool && $school->is($primarySchool)) {
                DB::table('shared_teacher_assignments')->updateOrInsert(
                    ['teacher_id' => $teacher->id, 'school_id' => $secondarySchool->id],
                    [
                        'allocation_percent' => 20,
                        'starts_on' => now()->startOfYear()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            if ($secondarySchool && $school->is($primarySchool)) {
                CrossSchoolTransfer::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'from_school_id' => $primarySchool->id,
                        'to_school_id' => $secondarySchool->id,
                    ],
                    [
                        'status' => 'pending',
                        'requested_by' => $admin->id,
                    ]
                );
            }
        }

        if ($primarySchool) {
            GroupPolicy::updateOrCreate(
                ['parent_school_id' => $primarySchool->id, 'policy_code' => 'GROUP-FEE-POLICY'],
                [
                    'name' => 'Group Fee Policy',
                    'rules' => ['currency' => 'USD', 'late_fee_percent' => 5],
                    'enforce_on_branches' => true,
                ]
            );
        }
    }
}
