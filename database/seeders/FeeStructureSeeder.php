<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\FeeStructure;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class FeeStructureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ClassModel::whereNotNull('school_id')->get() as $class) {
            foreach (ZimbabweData::FEE_CATEGORIES as $cat) {
                $amount = match ($cat) {
                    'Tuition Fees' => (float) [800, 1200, 1500, 2000][array_rand([800, 1200, 1500, 2000])],
                    'Development Levy' => 150.0,
                    'Examination Fees' => 50.0,
                    'Sports Levy' => 30.0,
                    'Library Fees' => 20.0,
                    'Lab Fees' => 40.0,
                    default => 50.0,
                };
                FeeStructure::firstOrCreate(
                    [
                        'school_id' => $class->school_id,
                        'class_id' => $class->id,
                        'category' => $cat,
                    ],
                    [
                        'class_name' => $class->name,
                        'amount' => $amount,
                        'currency' => 'USD',
                    ]
                );
            }
        }
    }
}
