<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            LicenseSeeder::class,
            TeacherSeeder::class,
            GradeLevelSeeder::class,
            ClassSeeder::class,
            SubjectSeeder::class,
            RoomSeeder::class,
            GradingScaleSeeder::class,
            TermSeeder::class,
            StudentSeeder::class,
            EnrollmentSeeder::class,
            GuardianSeeder::class,
            RelationIntegritySeeder::class,
            ParentStudentSeeder::class,
            TeacherAssignmentSeeder::class,
            TimetableSeeder::class,
            AttendanceSeeder::class,
            GradeSeeder::class,
            FeeStructureSeeder::class,
            InvoiceSeeder::class,
            PaymentSeeder::class,
            TransactionSeeder::class,
            PayrollSeeder::class,
            ReportTemplateSeeder::class,
            AssignmentSeeder::class,
            EnrollmentApplicationSeeder::class,
            AnnouncementSeeder::class,
            ExamSeeder::class,
            ExamResultSeeder::class,
            TestSeeder::class,
            TestResultSeeder::class,
            ErpModulesSeeder::class,
            OperationsSeeder::class,
            SchoolTripSeeder::class,
            AuditTrailSeeder::class,
            EnterpriseModulesSeeder::class,
            PlatformFeaturesSeeder::class,
            WorkflowSeeder::class,
            StudentPortalSeeder::class,
            TeacherPortalSeeder::class,
        ]);
    }
}
