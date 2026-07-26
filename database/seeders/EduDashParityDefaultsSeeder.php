<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use App\Models\Designation;
use App\Models\LeaveType;
use App\Models\School;
use App\Models\SchoolCurrency;
use App\Models\SchoolLanguage;
use App\Models\StudentCategory;
use Illuminate\Database\Seeder;

class EduDashParityDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        School::query()->each(function (School $school) {
            $this->seedLeaveTypes($school);
            $this->seedStudentCategories($school);
            $this->seedCurrencies($school);
            $this->seedLanguages($school);
            $this->seedDesignations($school);
            $this->seedCertificateTemplates($school);
        });
    }

    private function seedLeaveTypes(School $school): void
    {
        $defaults = [
            ['name' => 'Annual leave', 'code' => 'annual', 'default_days' => 21, 'is_paid' => true],
            ['name' => 'Sick leave', 'code' => 'sick', 'default_days' => 14, 'is_paid' => true],
            ['name' => 'Maternity leave', 'code' => 'maternity', 'default_days' => 90, 'is_paid' => true],
            ['name' => 'Unpaid leave', 'code' => 'unpaid', 'default_days' => null, 'is_paid' => false],
            ['name' => 'Other', 'code' => 'other', 'default_days' => null, 'is_paid' => false],
        ];

        foreach ($defaults as $row) {
            LeaveType::query()->firstOrCreate(
                ['school_id' => $school->id, 'name' => $row['name']],
                [
                    'code' => $row['code'],
                    'default_days' => $row['default_days'],
                    'is_paid' => $row['is_paid'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedStudentCategories(School $school): void
    {
        $defaults = [
            ['name' => 'Day scholar', 'code' => 'day'],
            ['name' => 'Boarder', 'code' => 'boarder'],
            ['name' => 'Bursary', 'code' => 'bursary'],
        ];

        foreach ($defaults as $i => $row) {
            StudentCategory::query()->firstOrCreate(
                ['school_id' => $school->id, 'name' => $row['name']],
                [
                    'code' => $row['code'],
                    'is_active' => true,
                    'order' => $i,
                ],
            );
        }
    }

    private function seedCurrencies(School $school): void
    {
        $defaultCode = strtoupper((string) ($school->currency_default ?? $school->currency ?? 'USD'));

        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'ZWG', 'name' => 'Zimbabwe Gold', 'symbol' => 'ZiG'],
        ];

        foreach ($currencies as $row) {
            SchoolCurrency::query()->firstOrCreate(
                ['school_id' => $school->id, 'code' => $row['code']],
                [
                    'name' => $row['name'],
                    'symbol' => $row['symbol'],
                    'is_default' => $row['code'] === $defaultCode,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedLanguages(School $school): void
    {
        $defaults = [
            ['code' => 'en', 'name' => 'English', 'is_default' => true],
            ['code' => 'sn', 'name' => 'Shona', 'is_default' => false],
            ['code' => 'nd', 'name' => 'Ndebele', 'is_default' => false],
        ];

        foreach ($defaults as $row) {
            SchoolLanguage::query()->firstOrCreate(
                ['school_id' => $school->id, 'code' => $row['code']],
                [
                    'name' => $row['name'],
                    'is_default' => $row['is_default'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedDesignations(School $school): void
    {
        $defaults = [
            ['name' => 'Head Teacher', 'code' => 'head'],
            ['name' => 'Deputy Head', 'code' => 'deputy'],
            ['name' => 'Teacher', 'code' => 'teacher'],
            ['name' => 'Bursar', 'code' => 'bursar'],
            ['name' => 'Clerk', 'code' => 'clerk'],
            ['name' => 'Driver', 'code' => 'driver'],
            ['name' => 'Security', 'code' => 'security'],
            ['name' => 'Grounds Staff', 'code' => 'grounds'],
        ];

        foreach ($defaults as $row) {
            Designation::query()->firstOrCreate(
                ['school_id' => $school->id, 'name' => $row['name']],
                [
                    'code' => $row['code'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedCertificateTemplates(School $school): void
    {
        $schoolName = e($school->name);
        $defaults = [
            [
                'name' => 'Character certificate',
                'certificate_type' => 'character',
                'title' => 'Certificate of Character',
                'body_html' => <<<HTML
<div style="font-family: Georgia, serif; max-width: 720px; margin: 2rem auto; padding: 2rem; border: 2px solid #1e3a5f; text-align: center;">
  <h1 style="margin-bottom: 0.5rem;">Certificate of Character</h1>
  <p style="color: #555;">{$schoolName}</p>
  <p style="margin-top: 2rem;">This is to certify that</p>
  <h2 style="margin: 1rem 0;">{{student_name}}</h2>
  <p>has been a student of good character at {{school_name}}.</p>
  <p style="margin-top: 2rem;">Issued on {{date}}</p>
  <p style="margin-top: 3rem; font-size: 0.9rem; color: #666;">Verify with code on school records</p>
</div>
HTML,
            ],
            [
                'name' => 'Completion certificate',
                'certificate_type' => 'completion',
                'title' => 'Certificate of Completion',
                'body_html' => <<<HTML
<div style="font-family: Georgia, serif; max-width: 720px; margin: 2rem auto; padding: 2rem; border: 2px solid #1e3a5f; text-align: center;">
  <h1 style="margin-bottom: 0.5rem;">Certificate of Completion</h1>
  <p style="color: #555;">{$schoolName}</p>
  <p style="margin-top: 2rem;">This certifies that</p>
  <h2 style="margin: 1rem 0;">{{student_name}}</h2>
  <p>has successfully completed their studies at {{school_name}}.</p>
  <p style="margin-top: 2rem;">Issued on {{date}}</p>
</div>
HTML,
            ],
            [
                'name' => 'Sports award',
                'certificate_type' => 'sports',
                'title' => 'Sports Achievement Certificate',
                'body_html' => <<<HTML
<div style="font-family: Georgia, serif; max-width: 720px; margin: 2rem auto; padding: 2rem; border: 2px solid #1e3a5f; text-align: center;">
  <h1 style="margin-bottom: 0.5rem;">Sports Achievement</h1>
  <p style="color: #555;">{$schoolName}</p>
  <p style="margin-top: 2rem;">Awarded to</p>
  <h2 style="margin: 1rem 0;">{{student_name}}</h2>
  <p>in recognition of outstanding sporting achievement at {{school_name}}.</p>
  <p style="margin-top: 2rem;">Issued on {{date}}</p>
</div>
HTML,
            ],
        ];

        foreach ($defaults as $row) {
            CertificateTemplate::query()->firstOrCreate(
                ['school_id' => $school->id, 'name' => $row['name']],
                [
                    'certificate_type' => $row['certificate_type'],
                    'title' => $row['title'],
                    'body_html' => $row['body_html'],
                    'is_active' => true,
                ],
            );
        }
    }
}
