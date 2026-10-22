<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\SchoolSettingsService;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Rich demo data for PreSkool-style UI: school profile, branches, photos, staff, documents, payments.
 */
class PreskoolUiDemoSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        $school = $this->resolveShowcaseSchool();
        if (! $school) {
            return;
        }

        $this->enrichSchoolRecord($school);
        $this->seedProfileSettings($school);
        $this->labelBranchCampuses($school);
        $this->seedStudentPhotos($school);
        $this->seedUserAvatars($school);
        $this->seedEmployees($school);
        $this->seedStudentDocuments($school);
    }

    private function resolveShowcaseSchool(): ?School
    {
        $admin = User::query()->where('email', 'admin@school.co.zw')->first();

        if ($admin?->school_id) {
            return School::query()->find($admin->school_id);
        }

        return School::query()->orderBy('id')->first();
    }

    private function enrichSchoolRecord(School $school): void
    {
        $year = (int) now()->year;
        $academicYear = "{$year} / ".($year + 1);

        $school->update([
            'name' => $school->name ?: 'Mufakose 1 High School',
            'status' => 'active',
            'principal_name' => 'Dr. Anitha Krishnan',
            'website' => 'www.'.strtolower($school->code).'.school.co.zw',
            'year_founded' => 2011,
            'city' => 'Harare',
            'suburb' => 'Mufakose',
            'motto' => 'Excellence through discipline and service',
            'timezone' => 'Africa/Harare',
            'currency' => $school->currency_default ?? 'USD',
            'academic_year' => $academicYear,
            'current_term' => 'Term 2',
            'student_capacity' => 2480,
            'phone' => $school->phone ?: ZimbabweData::phone(),
            'email' => $school->email ?: 'office@school.co.zw',
            'address' => $school->address ?: 'Survey No. 118, Mufakose, Harare',
        ]);
    }

    private function seedProfileSettings(School $school): void
    {
        /** @var SchoolSettingsService $settings */
        $settings = app(SchoolSettingsService::class);
        $settings->seedDefaults($school);

        $year = (int) now()->year;
        $academicYear = "{$year} / ".($year + 1);

        $settings->bulkSet($school, [
            ['group' => 'branding', 'key' => 'school_name', 'value' => $school->name],
            ['group' => 'branding', 'key' => 'motto', 'value' => $school->motto ?? 'Excellence through discipline and service'],
            ['group' => 'profile', 'key' => 'school_type', 'value' => 'Co-educational · Day and boarding'],
            ['group' => 'profile', 'key' => 'affiliation_board', 'value' => 'ZIMSEC · Zimbabwe School Examinations Council'],
            ['group' => 'profile', 'key' => 'medium_of_instruction', 'value' => 'English · Shona as second language'],
            ['group' => 'profile', 'key' => 'legal_name', 'value' => $school->name.' (Registered)'],
            ['group' => 'branding', 'key' => 'primary_color', 'value' => '#3D5EE1'],
            ['group' => 'branding', 'key' => 'secondary_color', 'value' => '#6FCCDB'],
            ['group' => 'branding', 'key' => 'success_color', 'value' => '#1ABE17'],
            ['group' => 'branding', 'key' => 'ink_color', 'value' => '#202C4B'],
            ['group' => 'branding', 'key' => 'apply_email', 'value' => true],
            ['group' => 'branding', 'key' => 'apply_certificate', 'value' => true],
            ['group' => 'branding', 'key' => 'apply_id_card', 'value' => true],
            ['group' => 'academic', 'key' => 'academic_year', 'value' => $academicYear],
            ['group' => 'academic', 'key' => 'current_term', 'value' => 'Term 2'],
            ['group' => 'campus', 'key' => 'area', 'value' => '12 hectares · 3 teaching blocks'],
            ['group' => 'campus', 'key' => 'classrooms', 'value' => '42 · 2 science labs, 1 computer lab'],
            ['group' => 'campus', 'key' => 'facilities', 'value' => 'Library, sports field, hall, clinic'],
            ['group' => 'campus', 'key' => 'second_language', 'value' => 'Shona'],
            ['group' => 'campus', 'key' => 'third_language', 'value' => 'Ndebele · Forms 1–4'],
            ['group' => 'campus', 'key' => 'campus_type', 'value' => 'Single campus · day scholars'],
            ['group' => 'campus', 'key' => 'session_type', 'value' => 'Annual · three terms'],
            ['group' => 'campus', 'key' => 'working_days', 'value' => 'Monday to Friday · Saturday sports'],
            ['group' => 'campus', 'key' => 'school_timings', 'value' => '07:30 – 15:30'],
            ['group' => 'campus', 'key' => 'exam_pattern', 'value' => 'Continuous assessment plus term exams'],
            ['group' => 'regional', 'key' => 'date_format', 'value' => 'd M Y'],
            ['group' => 'contact', 'key' => 'principal_office', 'value' => [
                'location' => 'Admin block, first floor',
                'phone' => $school->phone,
                'email' => 'principal@school.co.zw',
            ]],
            ['group' => 'contact', 'key' => 'reception', 'value' => [
                'location' => 'Main entrance',
                'phone' => $school->phone,
                'hours' => 'Mon–Fri, 07:30–16:30',
            ]],
            ['group' => 'contact', 'key' => 'phones', 'value' => [
                'main' => $school->phone,
                'admissions' => ZimbabweData::phone(),
                'transport' => ZimbabweData::phone(),
            ]],
            ['group' => 'contact', 'key' => 'emails', 'value' => [
                'office' => 'office@school.co.zw',
                'admissions' => 'admissions@school.co.zw',
                'accounts' => 'accounts@school.co.zw',
            ]],
            ['group' => 'contact', 'key' => 'emergency', 'value' => [
                'line' => ZimbabweData::phone(),
                'nurse' => ZimbabweData::phone(),
                'hospital' => '2.1 km',
            ]],
            ['group' => 'address', 'key' => 'office', 'value' => 'School Trust Office, Central Avenue, Harare'],
            ['group' => 'address', 'key' => 'campus', 'value' => $school->address],
            ['group' => 'address', 'key' => 'map_lat', 'value' => '-17.8252'],
            ['group' => 'address', 'key' => 'map_lng', 'value' => '31.0335'],
            ['group' => 'address', 'key' => 'map_verified', 'value' => true],
            ['group' => 'social', 'key' => 'facebook', 'value' => 'facebook.com/'.$school->code],
            ['group' => 'social', 'key' => 'twitter', 'value' => '@'.$school->code],
            ['group' => 'social', 'key' => 'instagram', 'value' => '@'.$school->code.'.school'],
            ['group' => 'social', 'key' => 'youtube', 'value' => 'youtube.com/@'.$school->code],
            ['group' => 'communication', 'key' => 'sms_sender_id', 'value' => strtoupper(substr($school->code, 0, 6))],
            ['group' => 'communication', 'key' => 'letterhead_template', 'value' => 'Standard · used on 6 document types'],
            ['group' => 'communication', 'key' => 'email_footer', 'value' => 'Address, phone and unsubscribe line'],
            ['group' => 'communication', 'key' => 'quiet_hours', 'value' => '21:00 – 07:00 · emergencies excepted'],
            ['group' => 'management', 'key' => 'people', 'value' => [
                ['role' => 'Principal', 'name' => 'Dr. Anitha Krishnan', 'email' => 'principal@school.co.zw', 'status' => 'In office'],
                ['role' => 'Vice Principal', 'name' => 'Mr. Suresh Iyer', 'email' => 'vice.principal@school.co.zw', 'status' => 'In office'],
                ['role' => 'Administrator', 'name' => 'Ms. Latha Rao', 'email' => 'admin@school.co.zw', 'status' => 'In office'],
                ['role' => 'Finance Officer', 'name' => 'Mr. Deepak Shetty', 'email' => 'finance@school.co.zw', 'status' => 'In office'],
            ]],
            ['group' => 'management', 'key' => 'trust_name', 'value' => $school->name.' Educational Trust'],
            ['group' => 'management', 'key' => 'trust_registration', 'value' => 'TR/HAR/2011/'.strtoupper($school->code)],
            ['group' => 'management', 'key' => 'trust_office', 'value' => 'Central Avenue, Harare'],
            ['group' => 'management', 'key' => 'committee_summary', 'value' => '7 members · last constituted May '.($year - 1)],
            ['group' => 'compliance', 'key' => 'items', 'value' => [
                [
                    'title' => 'Ministry registration',
                    'description' => 'Permanent secretary registration',
                    'number' => 'MOE/'.$school->code,
                    'valid_to' => 'Valid to 31 Dec '.($year + 3),
                    'status' => 'valid',
                    'status_label' => 'Valid',
                ],
                [
                    'title' => 'Fire safety clearance',
                    'description' => 'Municipal fire department',
                    'number' => 'FS/HAR/'.($year - 2),
                    'valid_to' => 'Expires 30 Sep '.($year + 1),
                    'status' => 'expiring',
                    'status_label' => '90 days left',
                ],
            ]],
            ['group' => 'documents', 'key' => 'items', 'value' => [
                ['name' => 'Registration certificate', 'mime' => 'PDF · 1.1 MB', 'uploaded_at' => now()->subMonths(2)->toIso8601String()],
                ['name' => 'School prospectus '.$academicYear, 'mime' => 'PDF · 2.4 MB', 'uploaded_at' => now()->subMonth()->toIso8601String()],
                ['name' => 'Policy pack summary', 'mime' => '7 files · admission, fees, conduct', 'uploaded_at' => now()->subWeeks(3)->toIso8601String()],
            ]],
            ['group' => 'security', 'key' => 'two_factor', 'value' => 'Required for administrators'],
            ['group' => 'security', 'key' => 'password_policy', 'value' => '12 characters · rotation every 180 days'],
            ['group' => 'security', 'key' => 'session_timeout', 'value' => '30 minutes idle'],
            ['group' => 'security', 'key' => 'ip_restriction', 'value' => ''],
            ['group' => 'security', 'key' => 'profile_editors', 'value' => '3 administrators'],
            ['group' => 'security', 'key' => 'data_retention', 'value' => 'Student records 7 years after leaving'],
            ['group' => 'security', 'key' => 'audit_retention', 'value' => '3 academic years'],
            ['group' => 'prefs', 'key' => 'academic_year_contact', 'value' => $school->principal_name],
            ['group' => 'prefs', 'key' => 'academic_year_support_email', 'value' => 'support@school.co.zw'],
            ['group' => 'prefs', 'key' => 'grading_rules_contact', 'value' => $school->principal_name],
            ['group' => 'prefs', 'key' => 'grading_rules_support_email', 'value' => 'exams@school.co.zw'],
            ['group' => 'prefs', 'key' => 'class_settings_contact', 'value' => $school->principal_name],
            ['group' => 'prefs', 'key' => 'class_settings_support_email', 'value' => 'academics@school.co.zw'],
            ['group' => 'prefs', 'key' => 'exam_settings_contact', 'value' => $school->principal_name],
            ['group' => 'prefs', 'key' => 'exam_settings_support_email', 'value' => 'exams@school.co.zw'],
            ['group' => 'prefs', 'key' => 'grading_rules_notes', 'value' => 'ZIMSEC letter grades · continuous assessment 30%'],
            ['group' => 'prefs', 'key' => 'class_settings_notes', 'value' => 'Roster changes require HOD sign-off during term time.'],
            ['group' => 'prefs', 'key' => 'exam_settings_notes', 'value' => 'Exam timetables published two weeks before each session.'],
            ['group' => 'prefs', 'key' => 'academic_year_notes', 'value' => 'Three-term calendar aligned with Ministry guidelines.'],
            ['group' => 'payments', 'key' => 'support_email', 'value' => 'fees@school.co.zw'],
            ['group' => 'payments', 'key' => 'settlement_account', 'value' => 'CBZ · A/c …4471'],
            ['group' => 'payments', 'key' => 'accept_cash', 'value' => true],
            ['group' => 'payments', 'key' => 'accept_bank', 'value' => true],
            ['group' => 'payments', 'key' => 'accept_mobile', 'value' => true],
            ['group' => 'payments', 'key' => 'published', 'value' => true],
        ]);
    }

    private function labelBranchCampuses(School $root): void
    {
        $branches = School::query()
            ->where('parent_school_id', $root->id)
            ->orderBy('id')
            ->get();

        $labels = [
            'North Campus',
            'South Campus',
            'East Campus',
            'West Campus',
            'Central Campus',
        ];
        $statuses = ['active', 'active', 'active', 'pending', 'active'];

        foreach ($branches as $index => $branch) {
            $branch->update([
                'name' => ($labels[$index] ?? 'Campus '.($index + 1)).' · '.$root->name,
                'branch_type' => 'branch',
                'status' => $statuses[$index] ?? 'active',
            ]);
        }
    }

    private function seedStudentPhotos(School $school): void
    {
        Student::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->each(function (Student $student) {
                $seed = urlencode($student->student_number ?: (string) $student->id);
                $student->update([
                    'photo_url' => "https://i.pravatar.cc/256?u={$seed}",
                ]);
            });
    }

    private function seedUserAvatars(School $school): void
    {
        User::query()
            ->where(function ($q) use ($school) {
                $q->where('school_id', $school->id)->orWhereNull('school_id');
            })
            ->whereNotNull('email')
            ->each(function (User $user) {
                $seed = urlencode($user->email);
                $user->update([
                    'avatar_url' => "https://i.pravatar.cc/256?u={$seed}",
                ]);
            });
    }

    private function seedEmployees(School $school): void
    {
        Department::query()->firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Administration'],
            ['code' => 'ADMIN', 'description' => 'Front office and school administration', 'is_active' => true],
        );

        $adminDept = Department::query()->where('school_id', $school->id)->where('name', 'Administration')->first();
        $sciDept = Department::query()->where('school_id', $school->id)->where('name', 'Sciences')->first();

        $clerk = Designation::query()->where('school_id', $school->id)->where('name', 'Clerk')->first();
        $bursar = Designation::query()->where('school_id', $school->id)->where('name', 'Bursar')->first();
        $teacher = Designation::query()->where('school_id', $school->id)->where('name', 'Teacher')->first();

        $staff = [
            ['num' => 'STF-2001', 'fn' => 'Teresa', 'ln' => 'Bru', 'status' => 'active', 'dept' => $adminDept?->id, 'des' => $clerk?->id],
            ['num' => 'STF-2002', 'fn' => 'Kevin', 'ln' => 'Larry', 'status' => 'active', 'dept' => $adminDept?->id, 'des' => $bursar?->id],
            ['num' => 'STF-2003', 'fn' => 'Rudo', 'ln' => 'Moyo', 'status' => 'on_leave', 'dept' => $sciDept?->id, 'des' => $teacher?->id],
            ['num' => 'STF-2004', 'fn' => 'Tendai', 'ln' => 'Sibanda', 'status' => 'probation', 'dept' => $adminDept?->id, 'des' => $clerk?->id],
            ['num' => 'STF-2005', 'fn' => 'Farai', 'ln' => 'Dube', 'status' => 'suspended', 'dept' => $sciDept?->id, 'des' => $teacher?->id],
            ['num' => 'STF-2006', 'fn' => 'Chenai', 'ln' => 'Ndlovu', 'status' => 'inactive', 'dept' => $adminDept?->id, 'des' => $clerk?->id],
            ['num' => 'STF-2007', 'fn' => 'Blessing', 'ln' => 'Chikomo', 'status' => 'active', 'dept' => $sciDept?->id, 'des' => $teacher?->id],
            ['num' => 'STF-2008', 'fn' => 'Kuda', 'ln' => 'Mutasa', 'status' => 'active', 'dept' => $adminDept?->id, 'des' => $bursar?->id],
        ];

        foreach ($staff as $i => $row) {
            Employee::updateOrCreate(
                ['school_id' => $school->id, 'employee_number' => $row['num']],
                [
                    'first_name' => $row['fn'],
                    'last_name' => $row['ln'],
                    'name' => $row['fn'].' '.$row['ln'],
                    'email' => strtolower($row['fn'].'.'.$row['ln']).'@school.co.zw',
                    'phone' => ZimbabweData::phone(),
                    'department_id' => $row['dept'],
                    'designation_id' => $row['des'],
                    'joining_date' => now()->subYears(rand(2, 9))->subMonths(rand(0, 11)),
                    'employment_type' => 'full_time',
                    'status' => $row['status'],
                    'base_salary' => 800 + ($i % 5) * 120,
                    'salary_currency' => 'USD',
                ],
            );
        }
    }

    private function seedStudentDocuments(School $school): void
    {
        Storage::disk('public')->makeDirectory("schools/{$school->id}/documents");

        $admin = $this->demoAdmin($school);
        $students = $this->demoStudents($school, 12);
        $types = ['birth_certificate', 'identity', 'admission', 'transfer_certificate', 'mark_sheet'];

        foreach ($students as $index => $student) {
            $type = $types[$index % count($types)];
            $fileName = "demo-{$student->id}-{$type}.pdf";
            $path = "schools/{$school->id}/documents/{$fileName}";

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, '%PDF-1.4 demo document for '.$student->full_name);
            }

            StudentDocument::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'name' => ucfirst(str_replace('_', ' ', $type)).' · '.$student->student_number,
                ],
                [
                    'uploaded_by' => $admin?->id,
                    'type' => $type,
                    'path' => $path,
                    'mime_type' => 'application/pdf',
                    'size' => Storage::disk('public')->size($path),
                    'is_favourite' => $index % 4 === 0,
                    'verified_at' => $index % 3 !== 0 ? now()->subDays($index + 2) : null,
                    'expires_at' => $index % 5 === 0 ? now()->addDays(20) : now()->addYear(),
                ],
            );
        }
    }
}
