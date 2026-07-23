<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\Student;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;

class AuditTrailSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        foreach ($this->demoSchools() as $school) {
            $admin = $this->demoAdmin($school);
            $teacher = $this->demoTeacherUser($school);
            $finance = \App\Models\User::query()
                ->where('school_id', $school->id)
                ->where('role', 'finance')
                ->first()
                ?? \App\Models\User::query()->where('email', 'finance@school.co.zw')->first();
            $student = $this->demoStudent($school);

            if (! $admin) {
                continue;
            }

            $events = [
                [
                    'user' => $admin,
                    'module' => 'students',
                    'action' => 'updated',
                    'description' => 'Updated learner contact details',
                    'days_ago' => 1,
                ],
                [
                    'user' => $teacher,
                    'module' => 'attendance',
                    'action' => 'created',
                    'description' => 'Marked class attendance register',
                    'days_ago' => 2,
                ],
                [
                    'user' => $finance,
                    'module' => 'finance',
                    'action' => 'created',
                    'description' => 'Recorded fee payment',
                    'days_ago' => 3,
                ],
                [
                    'user' => $admin,
                    'module' => 'hr',
                    'action' => 'approved',
                    'description' => 'Approved staff leave request',
                    'days_ago' => 4,
                ],
                [
                    'user' => $admin,
                    'module' => 'settings',
                    'action' => 'updated',
                    'description' => 'Updated school profile settings',
                    'days_ago' => 5,
                ],
            ];

            foreach ($events as $index => $event) {
                $actor = $event['user'] ?? $admin;
                if (! $actor) {
                    continue;
                }

                AuditLog::query()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'user_id' => $actor->id,
                        'module' => $event['module'],
                        'action' => $event['action'],
                        'description' => $event['description'],
                    ],
                    [
                        'auditable_type' => $student ? Student::class : null,
                        'auditable_id' => $student?->id,
                        'old_values' => null,
                        'new_values' => ['seed' => true, 'index' => $index],
                        'metadata' => ['source' => 'AuditTrailSeeder'],
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'DemoSeeder/1.0',
                        'device_type' => 'desktop',
                        'platform' => 'web',
                        'request_method' => 'POST',
                        'request_path' => '/api/v1/demo',
                        'created_at' => now()->subDays($event['days_ago'])->subMinutes($index * 7),
                    ]
                );
            }

            foreach ([$admin, $teacher, $finance, $this->demoParentUser($school)] as $user) {
                if (! $user) {
                    continue;
                }

                LoginHistory::query()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'user_id' => $user->id,
                        'event' => 'login',
                        'email' => $user->email,
                        'created_at' => now()->subHours(max(1, $user->id % 12)),
                    ],
                    [
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Mozilla/5.0 (Demo)',
                        'device_type' => 'desktop',
                        'platform' => 'web',
                        'location' => 'Harare, Zimbabwe',
                        'token_name' => 'demo-session',
                        'failure_reason' => null,
                    ]
                );
            }
        }
    }
}
