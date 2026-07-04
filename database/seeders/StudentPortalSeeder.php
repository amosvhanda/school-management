<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentPortalSeeder extends Seeder
{
    public function run(): void
    {
        $studentUser = User::where('email', 'student@school.co.zw')->first();
        if (! $studentUser) {
            return;
        }

        $school = $studentUser->school_id
            ? School::find($studentUser->school_id)
            : School::first();

        if (! $school) {
            return;
        }

        $classModel = ClassModel::where('school_id', $school->id)->orderBy('id')->first();
        if (! $classModel) {
            return;
        }

        $teacher = Teacher::where('school_id', $school->id)->orderBy('id')->first();

        // Guarantee this demo user is linked to an actual student record.
        $student = Student::where('school_id', $school->id)
            ->where('first_name', 'Nyasha')
            ->where('last_name', 'Chiremba')
            ->first();

        if (! $student) {
            $student = Student::create([
                'first_name' => 'Nyasha',
                'last_name' => 'Chiremba',
                'full_name' => 'Nyasha Chiremba',
                'student_number' => sprintf('%s-%s-PORTAL', $school->code, date('Y')),
                'date_of_birth' => '2008-04-15',
                'gender' => 'female',
                'phone' => '+263771234567',
                'email' => 'nyasha.chiremba@'.$school->code.'.school.co.zw',
                'address' => 'Mufakose, Harare',
                'suburb' => 'Mufakose',
                'class' => $classModel->name,
                'school' => $school->name,
                'status' => 'active',
                'balance' => 0,
                'currency' => 'USD',
                'school_id' => $school->id,
                'class_id' => $classModel->id,
                'grade_level_id' => $classModel->grade_level_id,
                'guardian_first_name' => 'Tatenda',
                'guardian_last_name' => 'Chiremba',
                'guardian_phone' => '+263772345678',
                'guardian_email' => 'parent@school.co.zw',
                'guardian_relationship' => 'parent',
            ]);
        }

        $student->update([
            'user_id' => $studentUser->id,
            'school_id' => $school->id,
            'class_id' => $classModel->id,
            'grade_level_id' => $classModel->grade_level_id,
            'class' => $classModel->name,
            'status' => 'active',
        ]);

        // Attendance history for recent weekdays.
        foreach (range(0, 13) as $daysAgo) {
            $date = now()->subDays($daysAgo);
            if ($date->isWeekend()) {
                continue;
            }
            $dateKey = $date->startOfDay()->format('Y-m-d H:i:s');

            $status = $daysAgo % 9 === 0 ? 'late' : ($daysAgo % 7 === 0 ? 'absent' : 'present');

            Attendance::query()->upsert(
                [[
                    'student_id' => $student->id,
                    'date' => $dateKey,
                    'class_id' => $classModel->id,
                    'school_id' => $school->id,
                    'teacher_id' => $teacher?->id,
                    'status' => $status,
                    'time_in' => $status === 'present' ? '07:28:00' : ($status === 'late' ? '07:49:00' : null),
                    'marked_by' => $teacher?->user_id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]],
                ['student_id', 'date', 'class_id'],
                ['school_id', 'teacher_id', 'status', 'time_in', 'marked_by', 'updated_at']
            );
        }

        // Grade records across core subjects.
        $subjects = Subject::where('school_id', $school->id)->orderBy('id')->take(4)->get();
        foreach ($subjects as $idx => $subject) {
            $score = [78, 82, 69, 74][$idx % 4];
            Grade::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'term' => 'Term 2',
                    'year' => (int) date('Y'),
                    'assessment_type' => 'exam',
                ],
                [
                    'school_id' => $school->id,
                    'class_id' => $classModel->id,
                    'teacher_id' => $teacher?->id,
                    'subject' => $subject->name,
                    'score' => $score,
                    'total' => 100,
                    'grade' => $score >= 80 ? 'A' : ($score >= 70 ? 'B' : 'C'),
                    'remarks' => $score >= 70 ? 'On track' : 'Needs revision support',
                ]
            );
        }

        // Fee structures and invoices for student portal billing history.
        $feeStructures = FeeStructure::where('school_id', $school->id)
            ->where('class_name', $classModel->name)
            ->orderBy('id')
            ->take(2)
            ->get();

        if ($feeStructures->isEmpty()) {
            $feeStructures = FeeStructure::where('school_id', $school->id)->orderBy('id')->take(2)->get();
        }

        foreach ($feeStructures as $i => $fee) {
            $invoiceNumber = sprintf('INV-%s-%s-SP%s', date('Y'), $student->id, $fee->id);
            $amount = (float) $fee->amount;

            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $invoiceNumber],
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'fee_structure_id' => $fee->id,
                    'description' => $fee->category.' - '.$classModel->name,
                    'amount' => $amount,
                    'amount_paid' => $i === 0 ? round($amount * 0.6, 2) : 0,
                    'balance' => $i === 0 ? round($amount * 0.4, 2) : $amount,
                    'currency' => $fee->currency ?? 'USD',
                    'due_date' => now()->addDays(14 + ($i * 10))->format('Y-m-d'),
                    'status' => $i === 0 ? 'partial' : 'pending',
                ]
            );

            if ($i === 0 && $invoice->amount_paid > 0) {
                Payment::updateOrCreate(
                    [
                        'invoice_id' => $invoice->id,
                        'reference' => 'STU-PORTAL-'.$invoice->id,
                    ],
                    [
                        'school_id' => $school->id,
                        'student_id' => $student->id,
                        'parent_id' => null,
                        'amount' => $invoice->amount_paid,
                        'currency' => $invoice->currency,
                        'method' => 'ecocash',
                        'status' => 'completed',
                        'date' => now()->subDays(4)->format('Y-m-d'),
                        'created_by' => $teacher?->user_id,
                    ]
                );
            }
        }

        // Ensure at least a few exam results are available for this student.
        $exams = Exam::where('school_id', $school->id)
            ->where('grade_level_id', $student->grade_level_id)
            ->orderBy('exam_date')
            ->take(3)
            ->get();

        foreach ($exams as $i => $exam) {
            $marks = [75, 81, 68][$i % 3];
            ExamResult::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                ],
                [
                    'school_id' => $school->id,
                    'subject_id' => $exam->subject_id,
                    'marks_obtained' => $marks,
                    'total_marks' => $exam->total_marks,
                    'percentage' => round(($marks / max(1, (float) $exam->total_marks)) * 100, 2),
                    'grade' => $marks >= 80 ? 'A' : ($marks >= 70 ? 'B' : 'C'),
                    'remarks' => $marks >= 70 ? 'Good performance' : 'Can improve with revision',
                ]
            );
        }
    }
}
