<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\BehaviorPoint;
use App\Models\ClassModel;
use App\Models\ClassParticipationRecord;
use App\Models\ClassSubstitution;
use App\Models\LeaveRequest;
use App\Models\LessonPlan;
use App\Models\OnlineLesson;
use App\Models\ReportCardNarrative;
use App\Models\School;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\StudentIntervention;
use App\Models\Subject;
use App\Models\SyllabusTopic;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherNotification;
use App\Models\TeachingResource;
use App\Models\Term;
use App\Models\TimetableChangeRequest;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic data so teacher@school.co.zw can exercise every Teacher Portal tab.
 */
class TeacherPortalSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        $user = User::query()->where('email', 'teacher@school.co.zw')->first();
        if (! $user || ! $user->school_id) {
            return;
        }

        $school = School::query()->find($user->school_id);
        if (! $school) {
            return;
        }

        $teacher = Teacher::query()
            ->where('school_id', $school->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('email', $user->email);
            })
            ->first();

        if (! $teacher) {
            return;
        }

        // Ensure user_id link for requireTeacher().
        if ((int) $teacher->user_id !== (int) $user->id) {
            $teacher->update(['user_id' => $user->id, 'email' => $user->email]);
        }

        $classIds = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereNotNull('class_id')
            ->pluck('class_id')
            ->unique()
            ->values();

        if ($classIds->isEmpty()) {
            $fallback = ClassModel::query()->where('school_id', $school->id)->orderBy('id')->take(2)->get();
            $subjects = Subject::query()->where('school_id', $school->id)->orderBy('id')->take(3)->get();
            foreach ($fallback as $class) {
                $class->update(['teacher_id' => $teacher->id]);
                TeacherAssignment::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'teacher_id' => $teacher->id,
                        'class_id' => $class->id,
                        'subject_id' => null,
                    ],
                    ['role' => 'class_teacher', 'assigned_at' => now(), 'is_active' => true]
                );
                foreach ($subjects as $subject) {
                    TeacherAssignment::updateOrCreate(
                        [
                            'school_id' => $school->id,
                            'teacher_id' => $teacher->id,
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                        ],
                        ['role' => 'subject_teacher', 'assigned_at' => now(), 'is_active' => true]
                    );
                }
            }
            $classIds = $fallback->pluck('id');
        }

        $classes = ClassModel::query()->whereIn('id', $classIds)->get();
        $primaryClass = $classes->first();
        $subject = Subject::query()
            ->where('school_id', $school->id)
            ->whereIn(
                'id',
                TeacherAssignment::query()
                    ->where('teacher_id', $teacher->id)
                    ->whereNotNull('subject_id')
                    ->pluck('subject_id')
            )
            ->first()
            ?? Subject::query()->where('school_id', $school->id)->first();

        $students = Student::query()
            ->where('school_id', $school->id)
            ->whereIn('class_id', $classIds)
            ->orderBy('id')
            ->limit(12)
            ->get();

        $term = Term::query()->where('school_id', $school->id)->orderByDesc('id')->first();
        $otherTeacher = Teacher::query()
            ->where('school_id', $school->id)
            ->where('id', '!=', $teacher->id)
            ->orderBy('id')
            ->first();

        $this->seedLessonPlans($school, $teacher, $primaryClass, $subject);
        $this->seedSyllabus($school, $teacher, $primaryClass, $subject);
        $this->seedResources($school, $teacher, $primaryClass, $subject);
        $this->seedOnlineLessons($school, $teacher, $primaryClass, $subject);
        $this->seedAssignmentsAndSubmissions($school, $teacher, $primaryClass, $students);
        $this->seedAttendanceSessions($school, $teacher, $primaryClass, $students, $subject);
        $this->seedBehaviourAndSupport($school, $teacher, $user, $students, $primaryClass, $subject);
        $this->seedReportCards($school, $teacher, $students, $primaryClass, $term);
        $this->seedLeaveAndCover($school, $teacher, $user, $otherTeacher, $primaryClass, $subject);
        $this->seedTimetableRequests($school, $teacher);
        $this->seedNotifications($school, $user);
        $this->seedCalendar($school, $teacher, $primaryClass);
        $this->seedAnnouncements($school);
    }

    private function seedLessonPlans(School $school, Teacher $teacher, ?ClassModel $class, ?Subject $subject): void
    {
        $plans = [
            ['title' => 'Introduction to Fractions', 'plan_type' => 'lesson', 'topic' => 'Fractions', 'status' => 'completed'],
            ['title' => 'Week 3 – Algebra basics', 'plan_type' => 'weekly', 'topic' => 'Algebra', 'status' => 'submitted'],
            ['title' => 'Term 2 overview', 'plan_type' => 'term', 'topic' => 'Curriculum map', 'status' => 'draft'],
        ];

        foreach ($plans as $i => $plan) {
            LessonPlan::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'teacher_id' => $teacher->id,
                    'title' => $plan['title'],
                ],
                [
                    'class_id' => $class?->id,
                    'subject_id' => $subject?->id,
                    'plan_type' => $plan['plan_type'],
                    'planned_date' => now()->subDays(7 - $i)->toDateString(),
                    'topic' => $plan['topic'],
                    'objectives' => 'Learners will understand key concepts and apply them in practice.',
                    'outcomes' => 'Learners can solve related problems independently.',
                    'activities' => "1. Starter\n2. Guided practice\n3. Independent work\n4. Plenary",
                    'resources' => 'Textbook, worksheets, whiteboard',
                    'status' => $plan['status'],
                    'completed_at' => $plan['status'] === 'completed' ? now()->subDays(5) : null,
                    'submitted_at' => in_array($plan['status'], ['submitted', 'completed'], true) ? now()->subDays(6) : null,
                ]
            );
        }
    }

    private function seedSyllabus(School $school, Teacher $teacher, ?ClassModel $class, ?Subject $subject): void
    {
        if (! $subject) {
            return;
        }

        $topics = [
            ['title' => 'Number systems', 'chapter' => 'Chapter 1', 'status' => 'completed', 'coverage_percent' => 100],
            ['title' => 'Fractions and decimals', 'chapter' => 'Chapter 2', 'status' => 'in_progress', 'coverage_percent' => 60],
            ['title' => 'Algebraic expressions', 'chapter' => 'Chapter 3', 'status' => 'planned', 'coverage_percent' => 0],
            ['title' => 'Geometry foundations', 'chapter' => 'Chapter 4', 'status' => 'planned', 'coverage_percent' => 0],
        ];

        foreach ($topics as $i => $topic) {
            SyllabusTopic::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'title' => $topic['title'],
                ],
                [
                    'class_id' => $class?->id,
                    'chapter' => $topic['chapter'],
                    'sort_order' => $i + 1,
                    'objectives' => 'Master the core ideas in '.$topic['title'],
                    'status' => $topic['status'],
                    'coverage_percent' => $topic['coverage_percent'],
                    'completed_on' => $topic['status'] === 'completed' ? now()->subDays(10)->toDateString() : null,
                ]
            );
        }
    }

    private function seedResources(School $school, Teacher $teacher, ?ClassModel $class, ?Subject $subject): void
    {
        $items = [
            ['title' => 'Fractions notes (PDF)', 'resource_type' => 'pdf', 'shared' => true],
            ['title' => 'Algebra worksheet 1', 'resource_type' => 'worksheet', 'shared' => false],
            ['title' => 'Lesson slides – geometry', 'resource_type' => 'presentation', 'shared' => true],
            ['title' => 'Revision video link', 'resource_type' => 'video', 'shared' => true],
        ];

        foreach ($items as $item) {
            TeachingResource::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'teacher_id' => $teacher->id,
                    'title' => $item['title'],
                ],
                [
                    'subject_id' => $subject?->id,
                    'class_id' => $class?->id,
                    'resource_type' => $item['resource_type'],
                    'topic' => 'Term 2',
                    'term' => 'Term 2',
                    'file_url' => '/storage/demo/'.str_replace(' ', '-', strtolower($item['title'])).'.pdf',
                    'description' => 'Demo teaching resource for the teacher portal.',
                    'shared' => $item['shared'],
                ]
            );
        }
    }

    private function seedOnlineLessons(School $school, Teacher $teacher, ?ClassModel $class, ?Subject $subject): void
    {
        $lessons = [
            [
                'title' => 'Live: Fractions catch-up',
                'lesson_type' => 'live',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDay()->setTime(10, 0),
                'meeting_url' => 'https://meet.example.com/demo-fractions',
            ],
            [
                'title' => 'Recorded: Algebra intro',
                'lesson_type' => 'recorded',
                'status' => 'published',
                'scheduled_at' => now()->subDays(2),
                'recording_url' => 'https://video.example.com/algebra-intro',
            ],
            [
                'title' => 'Class quiz: Decimals',
                'lesson_type' => 'quiz',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(3)->setTime(14, 0),
            ],
        ];

        foreach ($lessons as $lesson) {
            OnlineLesson::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'teacher_id' => $teacher->id,
                    'title' => $lesson['title'],
                ],
                [
                    'class_id' => $class?->id,
                    'subject_id' => $subject?->id,
                    'lesson_type' => $lesson['lesson_type'],
                    'scheduled_at' => $lesson['scheduled_at'],
                    'meeting_url' => $lesson['meeting_url'] ?? null,
                    'recording_url' => $lesson['recording_url'] ?? null,
                    'description' => 'Demo LMS activity for the teacher portal.',
                    'status' => $lesson['status'],
                ]
            );
        }
    }

    private function seedAssignmentsAndSubmissions(
        School $school,
        Teacher $teacher,
        ?ClassModel $class,
        $students
    ): void {
        if (! $class) {
            return;
        }

        $assignment = Assignment::updateOrCreate(
            [
                'school_id' => $school->id,
                'teacher_id' => $teacher->id,
                'title' => 'Fractions homework pack',
            ],
            [
                'description' => 'Complete exercises 1–10.',
                'instructions' => 'Show all working. Submit as PDF or photo.',
                'class_id' => $class->id,
                'subject' => 'Mathematics',
                'due_date' => now()->addDays(5)->toDateString(),
                'total_marks' => 20,
                'status' => 'open',
                'submission_type' => 'file',
                'submissions_count' => 0,
            ]
        );

        $count = 0;
        foreach ($students->take(5) as $i => $student) {
            AssignmentSubmission::updateOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                ],
                [
                    'school_id' => $school->id,
                    'status' => $i === 0 ? 'graded' : 'submitted',
                    'file_url' => '/storage/demo/submission-'.$student->id.'.pdf',
                    'content' => null,
                    'score' => $i === 0 ? 16 : null,
                    'teacher_comment' => $i === 0 ? 'Good effort — check Q7.' : null,
                    'submitted_at' => now()->subDays(1),
                    'graded_at' => $i === 0 ? now() : null,
                ]
            );
            $count++;
        }

        $assignment->update(['submissions_count' => $count]);
    }

    private function seedAttendanceSessions(
        School $school,
        Teacher $teacher,
        ?ClassModel $class,
        $students,
        ?Subject $subject
    ): void {
        if (! $class || $students->isEmpty()) {
            return;
        }

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        foreach ([$yesterday, $today] as $date) {
            $session = AttendanceSession::query()
                ->where('school_id', $school->id)
                ->where('class_id', $class->id)
                ->whereDate('date', $date)
                ->where('subject_id', $subject?->id)
                ->where('period', 'P1')
                ->first();

            $sessionPayload = [
                'school_id' => $school->id,
                'class_id' => $class->id,
                'date' => $date,
                'subject_id' => $subject?->id,
                'period' => 'P1',
                'teacher_id' => $teacher->id,
                'submitted_at' => $date === $yesterday ? now()->subDay() : null,
                'locked_at' => null,
            ];

            if ($session) {
                $session->update($sessionPayload);
            } else {
                AttendanceSession::create($sessionPayload);
            }

            foreach ($students->take(8) as $i => $student) {
                $status = $i === 3 ? 'absent' : ($i === 4 ? 'late' : ($i === 5 ? 'sick' : 'present'));
                $row = Attendance::query()
                    ->where('school_id', $school->id)
                    ->where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->whereDate('date', $date)
                    ->first();

                $payload = [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'date' => $date,
                    'status' => $status,
                    'remarks' => $i === 3 ? 'No reason given' : null,
                    'time_in' => $i === 4 ? '08:25:00' : '07:55:00',
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject?->id,
                    'marked_by' => $teacher->user_id,
                    'submitted_at' => $date === $yesterday ? now()->subDay() : null,
                ];

                if ($row) {
                    $row->update($payload);
                } else {
                    Attendance::create($payload);
                }
            }
        }
    }

    private function seedBehaviourAndSupport(
        School $school,
        Teacher $teacher,
        User $user,
        $students,
        ?ClassModel $class,
        ?Subject $subject
    ): void {
        foreach ($students->take(4) as $i => $student) {
            BehaviorPoint::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'description' => $i % 2 === 0 ? 'Helped classmates during group work' : 'Late homework warning',
                    'recorded_on' => now()->subDays($i + 1)->toDateString(),
                ],
                [
                    'points' => $i % 2 === 0 ? 2 : -1,
                    'category' => $i % 2 === 0 ? 'positive' : 'warning',
                    'recorded_by' => $user->id,
                ]
            );

            ClassParticipationRecord::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'recorded_on' => now()->subDays($i)->toDateString(),
                ],
                [
                    'class_id' => $class?->id,
                    'subject_id' => $subject?->id,
                    'score' => 3 + ($i % 3),
                    'notes' => 'Demo participation score',
                ]
            );
        }

        if ($students->isNotEmpty()) {
            $weak = $students->last();
            StudentIntervention::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $weak->id,
                    'intervention_type' => 'academic_support',
                ],
                [
                    'status' => 'open',
                    'summary' => 'Struggling with fractions — needs extra practice.',
                    'action_plan' => 'Two after-class sessions this week; notify guardian.',
                    'assigned_to' => $user->id,
                    'created_by' => $user->id,
                    'start_date' => now()->toDateString(),
                    'follow_up_date' => now()->addWeek()->toDateString(),
                ]
            );
        }
    }

    private function seedReportCards(
        School $school,
        Teacher $teacher,
        $students,
        ?ClassModel $class,
        ?Term $term
    ): void {
        foreach ($students->take(3) as $student) {
            ReportCardNarrative::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'term_id' => $term?->id,
                ],
                [
                    'school_id' => $school->id,
                    'class_id' => $class?->id,
                    'academic_comment' => 'Steady progress this term with good class engagement.',
                    'behaviour_comment' => 'Respectful and cooperative in class.',
                    'recommendations' => 'Continue practice at home; revise weak topics weekly.',
                    'strengths' => 'Participation and teamwork',
                    'areas_for_improvement' => 'Exam technique under timed conditions',
                    'status' => 'draft',
                ]
            );
        }
    }

    private function seedLeaveAndCover(
        School $school,
        Teacher $teacher,
        User $user,
        ?Teacher $otherTeacher,
        ?ClassModel $class,
        ?Subject $subject
    ): void {
        LeaveRequest::updateOrCreate(
            [
                'school_id' => $school->id,
                'teacher_id' => $teacher->id,
                'start_date' => now()->addDays(14)->toDateString(),
                'type' => 'annual',
            ],
            [
                'requested_by' => $user->id,
                'end_date' => now()->addDays(15)->toDateString(),
                'days' => 2,
                'reason' => 'Family commitment (demo leave request)',
                'status' => 'pending',
            ]
        );

        LeaveRequest::updateOrCreate(
            [
                'school_id' => $school->id,
                'teacher_id' => $teacher->id,
                'start_date' => now()->subDays(30)->toDateString(),
                'type' => 'sick',
            ],
            [
                'requested_by' => $user->id,
                'end_date' => now()->subDays(29)->toDateString(),
                'days' => 1,
                'reason' => 'Illness (demo approved leave)',
                'status' => 'approved',
                'reviewed_by' => $this->demoAdmin($school)?->id,
                'reviewed_at' => now()->subDays(28),
            ]
        );

        if ($otherTeacher && $class) {
            // Cover others can accept (demo teacher is substitute).
            ClassSubstitution::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'class_id' => $class->id,
                    'absent_teacher_id' => $otherTeacher->id,
                    'date' => now()->addDay()->toDateString(),
                    'period' => 'P3',
                ],
                [
                    'subject_id' => $subject?->id,
                    'substitute_teacher_id' => null,
                    'status' => 'open',
                    'notes' => 'Open cover request — accept from Teaching → Cover',
                ]
            );

            // Demo teacher's own upcoming absence with requested cover.
            ClassSubstitution::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'class_id' => $class->id,
                    'absent_teacher_id' => $teacher->id,
                    'date' => now()->addDays(14)->toDateString(),
                    'period' => 'P2',
                ],
                [
                    'subject_id' => $subject?->id,
                    'substitute_teacher_id' => $otherTeacher->id,
                    'status' => 'accepted',
                    'notes' => 'Cover arranged for annual leave',
                ]
            );
        }
    }

    private function seedTimetableRequests(School $school, Teacher $teacher): void
    {
        TimetableChangeRequest::updateOrCreate(
            [
                'school_id' => $school->id,
                'teacher_id' => $teacher->id,
                'details' => 'Please swap Thursday P2 with Friday P4 (demo change request).',
            ],
            [
                'request_type' => 'change',
                'preferred_slot' => 'Friday P4',
                'status' => 'pending',
            ]
        );
    }

    private function seedNotifications(School $school, User $user): void
    {
        $items = [
            ['type' => 'assignment', 'title' => 'New homework submission', 'body' => 'A student submitted Fractions homework pack.', 'link' => '/teaching?tab=homework'],
            ['type' => 'attendance', 'title' => 'Attendance reminder', 'body' => 'Mark today’s register for your first class.', 'link' => '/academics/attendance'],
            ['type' => 'exam', 'title' => 'Exam reminder', 'body' => 'Mid-term exams start next week.', 'link' => '/academics/exams'],
            ['type' => 'announcement', 'title' => 'Staff meeting', 'body' => 'Curriculum planning meeting on Tuesday.', 'link' => '/communications'],
        ];

        foreach ($items as $i => $item) {
            TeacherNotification::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'title' => $item['title'],
                ],
                [
                    'type' => $item['type'],
                    'body' => $item['body'],
                    'link' => $item['link'],
                    'read_at' => $i === 3 ? now() : null,
                ]
            );
        }
    }

    private function seedCalendar(School $school, Teacher $teacher, ?ClassModel $class): void
    {
        if (! class_exists(SchoolEvent::class)) {
            return;
        }

        SchoolEvent::updateOrCreate(
            [
                'school_id' => $school->id,
                'title' => 'Class test – '.($class?->name ?? 'Form 1'),
            ],
            [
                'description' => 'In-class assessment scheduled by demo teacher.',
                'type' => 'academic',
                'starts_at' => now()->addDays(4)->setTime(9, 0),
                'ends_at' => now()->addDays(4)->setTime(10, 0),
                'location' => $class?->name,
                'status' => 'scheduled',
                'created_by' => $teacher->user_id,
            ]
        );
    }

    private function seedAnnouncements(School $school): void
    {
        Announcement::updateOrCreate(
            [
                'school_id' => $school->id,
                'title' => 'Department update – Sciences',
            ],
            [
                'message' => 'Please submit week lesson plans by Friday. Demo announcement for teacher dashboard.',
                'type' => 'important',
                'target_audience' => 'teachers',
                'date' => now()->toDateString(),
                'is_active' => true,
                'created_by' => $this->demoAdmin($school)?->id,
            ]
        );
    }
}
