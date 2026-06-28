<?php

namespace Database\Seeders;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /**
     * Create invoices for students and link them to parents.
     */
    public function run(): void
    {
        $year = date('Y');

        foreach (Student::where('status', 'active')->whereNotNull('school_id')->get() as $student) {
            $fees = FeeStructure::where('school_id', $student->school_id)
                ->where('class_name', $student->class)
                ->get();
            
            if ($fees->isEmpty()) {
                continue;
            }

            // Get parent for this student (from parent_student pivot table)
            $parentRecord = DB::table('parent_student')
                ->where('student_id', $student->id)
                ->where('is_primary', true)
                ->first();
            
            $parent = $parentRecord ? User::find($parentRecord->parent_id) : null;

            foreach ($fees as $fee) {
                $invNum = 'INV-' . $year . '-' . $student->school_id . '-' . $student->id . '-' . $fee->id;
                Invoice::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'fee_structure_id' => $fee->id,
                    ],
                    [
                        'invoice_number' => $invNum,
                        'description' => $fee->category . ' – ' . $fee->class_name,
                        'amount' => $fee->amount,
                        'amount_paid' => 0,
                        'balance' => $fee->amount,
                        'currency' => $fee->currency ?? 'USD',
                        'due_date' => now()->addDays(30)->format('Y-m-d'),
                        'status' => 'pending',
                        'school_id' => $student->school_id,
                        'parent_id' => $parent?->id, // Link to parent
                    ]
                );
            }
        }
    }
}
