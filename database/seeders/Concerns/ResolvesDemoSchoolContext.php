<?php

namespace Database\Seeders\Concerns;

use App\Models\ClassModel;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait ResolvesDemoSchoolContext
{
    protected function demoSchools(): Collection
    {
        return School::query()->orderBy('id')->get();
    }

    protected function demoAdmin(School $school): ?User
    {
        return User::query()
            ->where('school_id', $school->id)
            ->where('role', 'admin')
            ->first()
            ?? User::query()->where('email', 'admin@school.co.zw')->first();
    }

    protected function demoTeacher(School $school): ?Teacher
    {
        return Teacher::query()->where('school_id', $school->id)->first();
    }

    protected function demoTeacherUser(School $school): ?User
    {
        return User::query()
            ->where('school_id', $school->id)
            ->where('role', 'teacher')
            ->first()
            ?? User::query()->where('email', 'teacher@school.co.zw')->first();
    }

    protected function demoParentUser(School $school): ?User
    {
        return User::query()
            ->where('school_id', $school->id)
            ->where('role', 'parent')
            ->first()
            ?? User::query()->where('email', 'parent@school.co.zw')->first();
    }

    protected function demoStudents(School $school, int $limit = 5): Collection
    {
        return Student::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    protected function demoStudent(School $school): ?Student
    {
        return $this->demoStudents($school, 1)->first();
    }

    protected function demoDepartment(School $school): ?Department
    {
        return Department::query()->where('school_id', $school->id)->first();
    }

    protected function demoSubject(School $school): ?Subject
    {
        return Subject::query()->where('school_id', $school->id)->first();
    }

    protected function demoTerm(School $school): ?Term
    {
        return Term::query()->where('school_id', $school->id)->first();
    }

    protected function demoRoom(School $school): ?Room
    {
        return Room::query()->where('school_id', $school->id)->first();
    }

    protected function demoClass(School $school): ?ClassModel
    {
        return ClassModel::query()->where('school_id', $school->id)->first();
    }

    protected function demoInvoice(School $school): ?Invoice
    {
        $student = $this->demoStudent($school);

        if (! $student) {
            return null;
        }

        return Invoice::query()
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->first();
    }

    protected function demoPayment(School $school): ?Payment
    {
        $student = $this->demoStudent($school);

        if (! $student) {
            return null;
        }

        return Payment::query()
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->first();
    }

    protected function demoExam(School $school): ?Exam
    {
        return Exam::query()->where('school_id', $school->id)->first();
    }

    protected function demoExamResult(School $school): ?ExamResult
    {
        return ExamResult::query()
            ->whereHas('exam', fn ($query) => $query->where('school_id', $school->id))
            ->orderBy('id')
            ->first();
    }

    protected function demoDate(int $month, int $day): Carbon
    {
        return now()->create((int) now()->year, $month, $day)->startOfDay();
    }
}
