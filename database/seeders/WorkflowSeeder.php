<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\PurchaseRequisition;
use App\Models\School;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflows = app(WorkflowService::class);

        foreach (School::all() as $school) {
            $admin = User::where('school_id', $school->id)
                ->whereIn('role', ['admin', 'school_admin'])
                ->first();

            if (! $admin) {
                continue;
            }

            $workflows->ensureDefinitions($school->id);

            $requisition = PurchaseRequisition::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'title' => 'Science lab reagents — pending approval',
                ],
                [
                    'requested_by' => $admin->id,
                    'description' => 'Demo procurement workflow awaiting admin approval.',
                    'estimated_cost' => 680.00,
                    'status' => 'pending_approval',
                ]
            );

            if (! $requisition->workflow_instance_id) {
                $instance = $workflows->start('purchase_request', $requisition, $admin, [
                    'title' => $requisition->title,
                ]);
                $requisition->update(['workflow_instance_id' => $instance->id]);
            }

            $invoice = Invoice::query()
                ->where('school_id', $school->id)
                ->where('balance', '>', 0)
                ->first();

            if ($invoice) {
                $existing = \App\Models\WorkflowInstance::query()
                    ->where('school_id', $school->id)
                    ->where('subject_type', Invoice::class)
                    ->where('subject_id', $invoice->id)
                    ->where('status', 'pending')
                    ->exists();

                if (! $existing) {
                    $workflows->start('fee_waiver', $invoice, $admin, [
                        'title' => 'Fee waiver — '.$invoice->invoice_number,
                        'student_id' => $invoice->student_id,
                    ]);
                }
            }
        }
    }
}
