<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Order: Schools → Roles → Users → Teachers → GradeLevels → Classes → Subjects → Rooms →
     *        GradingScales → Terms → Students → Enrollment → Guardian → ParentStudent →
     *        TeacherAssignments → Timetable → Attendance → Grades → Fee Structures →
     *        Invoices → Payments → Transactions → Payroll → Settings → ReportTemplates →
     *        Assignments → EnrollmentApplications → Announcements → Exams → ExamResults →
     *        Tests → TestResults.
     *
     * IMPORTANT: Order matters for relationships!
     */
    public function run(): void
    {
        $this->call([
            // 1. Core entities (no dependencies)
            SchoolSeeder::class,
            RoleSeeder::class,

            // 2. Users (depends on Schools)
            UserSeeder::class,

            // 2c. Reference data used across ERP modules
            CoreReferenceSeeder::class,

            // 3. Teachers (depends on Schools, Users)
            TeacherSeeder::class,

            // 4. Grade levels (depends on Schools) — before classes/students for relation filters
            GradeLevelSeeder::class,

            // 5. Classes (depends on Schools, Teachers, GradeLevels)
            ClassSeeder::class,

            // 6. Subjects (depends on Schools, Classes, Teachers)
            SubjectSeeder::class,

            // 7. Rooms (depends on Schools)
            RoomSeeder::class,

            // 8. Grading Scales (depends on Schools)
            GradingScaleSeeder::class,

            // 9. Terms (depends on Schools)
            TermSeeder::class,

            // 10. Students (depends on Schools, Classes, GradeLevels)
            StudentSeeder::class,

            // 11. Enrollments (depends on Students, Classes) - Links students to classes
            EnrollmentSeeder::class,

            // 12. Guardians (depends on Schools, Students) - Creates guardians and links to students
            GuardianSeeder::class,

            // 12b. Backfill class/grade/guardian links for searchable relation fields
            RelationIntegritySeeder::class,

            // 13. Parent-Student relationships (depends on Users, Students)
            ParentStudentSeeder::class,

            // 14. Teacher Assignments (depends on Teachers, Classes, Subjects, GradeLevels)
            TeacherAssignmentSeeder::class,

            // 15. Timetable (depends on Classes, Teachers, Subjects, Rooms)
            TimetableSeeder::class,

            // 16. Attendance (depends on Students, Classes)
            AttendanceSeeder::class,

            // 17. Grades (depends on Students, Classes, Teachers, Subjects)
            GradeSeeder::class,

            // 18. Fee Structures (depends on Schools, Classes)
            FeeStructureSeeder::class,

            // 19. Invoices (depends on Students, Fee Structures, Parents)
            InvoiceSeeder::class,

            // 20. Payments (depends on Invoices, Students, Parents)
            PaymentSeeder::class,

            // 21. Transactions (depends on Students, Payments, Invoices)
            TransactionSeeder::class,

            // 22. Payroll (depends on Teachers)
            PayrollSeeder::class,

            // 23. Settings (depends on Schools)
            SettingSeeder::class,

            // 24. Report Templates (depends on Schools)
            ReportTemplateSeeder::class,

            // 25. Assignments (depends on Classes, Teachers, Subjects)
            AssignmentSeeder::class,

            // 26. Enrollment Applications (depends on Schools)
            EnrollmentApplicationSeeder::class,

            // 27. Announcements (depends on Schools)
            AnnouncementSeeder::class,

            // 28. Exams (depends on Schools, Terms, GradeLevels, Subjects)
            ExamSeeder::class,

            // 29. Exam Results (depends on Exams, Students, Subjects)
            ExamResultSeeder::class,

            // 30. Tests (depends on Schools, Classes, Subjects, Teachers, Terms)
            TestSeeder::class,

            // 31. Test Results (depends on Tests, Students, Subjects)
            TestResultSeeder::class,

            // 32. Teacher portal demo data (assignments, lessons, LMS, leave, etc.)
            TeacherPortalSeeder::class,

            // 33. EduDash parity defaults (leave types, student categories, currencies)
            EduDashParityDefaultsSeeder::class,
        ]);

        if (app()->environment(['local', 'testing']) || filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
