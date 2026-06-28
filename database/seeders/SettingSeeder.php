<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $year = (string) now()->year;
        foreach (School::all() as $school) {
            $items = [
                ['key' => 'academic.academicYear', 'value' => $year, 'type' => 'string', 'category' => 'academic'],
                ['key' => 'academic.currentTerm', 'value' => 'Term 1', 'type' => 'string', 'category' => 'academic'],
                ['key' => 'general.schoolName', 'value' => $school->name, 'type' => 'string', 'category' => 'general'],
                ['key' => 'general.currency', 'value' => $school->currency_default ?? 'USD', 'type' => 'string', 'category' => 'general'],
            ];
            foreach ($items as $item) {
                Setting::updateOrCreate(
                    ['school_id' => $school->id, 'key' => $item['key']],
                    [
                        'value' => $item['value'],
                        'type' => $item['type'],
                        'category' => $item['category'],
                    ]
                );
            }
        }
    }
}
