<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class GuardianService
{
    /**
     * Auto-create or find guardian during student admission
     */
    public function createOrFindGuardian(array $guardianData, int $schoolId, ?int $studentId = null): Guardian
    {
        return DB::transaction(function () use ($guardianData, $schoolId, $studentId) {
            // Check if guardian exists by email or phone
            $guardian = Guardian::where('school_id', $schoolId)
                ->where(function ($query) use ($guardianData) {
                    if (!empty($guardianData['email'])) {
                        $query->where('email', $guardianData['email']);
                    }
                    if (!empty($guardianData['phone'])) {
                        $query->orWhere('phone', $guardianData['phone']);
                    }
                })
                ->first();

            if (!$guardian) {
                // Create guardian
                $guardian = Guardian::create([
                    'school_id' => $schoolId,
                    'first_name' => $guardianData['first_name'] ?? '',
                    'last_name' => $guardianData['last_name'] ?? '',
                    'email' => $guardianData['email'] ?? null,
                    'phone' => $guardianData['phone'] ?? '',
                    'relationship' => $guardianData['relationship'] ?? 'parent',
                    'address' => $guardianData['address'] ?? null,
                    'national_id' => $guardianData['national_id'] ?? null,
                    'occupation' => $guardianData['occupation'] ?? null,
                    'is_primary' => $guardianData['is_primary'] ?? true,
                    'can_receive_notifications' => $guardianData['can_receive_notifications'] ?? true,
                ]);

                // Create user account for guardian if email provided
                if (!empty($guardianData['email'])) {
                    $user = User::firstOrCreate(
                        [
                            'email' => $guardianData['email'],
                            'school_id' => $schoolId,
                        ],
                        [
                            'name' => "{$guardian->first_name} {$guardian->last_name}",
                            'first_name' => $guardian->first_name,
                            'last_name' => $guardian->last_name,
                            'phone' => $guardian->phone,
                            'password' => Hash::make('password123'), // Default password, should be changed on first login
                            'role' => UserRole::Parent,
                        ]
                    );

                    if ((int) $user->school_id !== (int) $schoolId) {
                        $user->update(['school_id' => $schoolId]);
                    }

                    $guardian->update(['user_id' => $user->id]);
                }
            }

            // Link guardian to student if provided
            if ($studentId) {
                Student::where('school_id', $schoolId)->findOrFail($studentId);

                $pivot = [
                    'relationship' => $guardianData['relationship'] ?? 'parent',
                    'is_primary' => $guardianData['is_primary'] ?? true,
                    'can_pickup' => $guardianData['can_pickup'] ?? true,
                    'emergency_contact' => $guardianData['emergency_contact'] ?? false,
                ];

                $guardian->students()->syncWithoutDetaching([$studentId => $pivot]);
                $this->syncParentUserLink($guardian, $studentId, $pivot);
            }

            return $guardian;
        });
    }

    /**
     * Keep parent_student pivot aligned when a guardian has a portal user account.
     */
    public function syncParentUserLink(Guardian $guardian, int $studentId, array $pivot = []): void
    {
        if (! $guardian->user_id) {
            return;
        }

        $user = User::find($guardian->user_id);
        if (! $user || $user->role !== UserRole::Parent) {
            return;
        }

        $user->students()->syncWithoutDetaching([
            $studentId => [
                'school_id' => $guardian->school_id,
                'relationship' => $pivot['relationship'] ?? $guardian->relationship ?? 'parent',
                'is_primary' => $pivot['is_primary'] ?? $guardian->is_primary ?? true,
            ],
        ]);
    }

    /**
     * Get guardians for a student
     */
    public function getStudentGuardians(int $studentId): \Illuminate\Database\Eloquent\Collection
    {
        $student = Student::findOrFail($studentId);
        return $student->guardians;
    }

    /**
     * Get students for a guardian
     */
    public function getGuardianStudents(int $guardianId): \Illuminate\Database\Eloquent\Collection
    {
        $guardian = Guardian::findOrFail($guardianId);
        return $guardian->students;
    }
}
