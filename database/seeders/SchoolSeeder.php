<?php

namespace Database\Seeders;

use App\Models\School;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ZimbabweData::SCHOOLS as $s) {
            School::updateOrCreate(
                ['code' => $s['code']],
                [
                    'name' => $s['name'],
                    'address' => 'Harare, Zimbabwe',
                    'phone' => ZimbabweData::phone(),
                    'email' => 'info@' . strtolower(preg_replace('/[^a-z0-9]/', '', $s['code'])) . '.school.co.zw',
                    'currency_default' => 'USD',
                    'academic_year' => (string) now()->year,
                    'current_term' => 'Term 1',
                    'settings' => null,
                ]
            );
        }
    }
}
