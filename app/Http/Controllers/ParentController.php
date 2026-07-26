<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ParentAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ParentController extends Controller
{
    public function __construct(private ParentAccessService $parentAccess) {}

    /**
     * Get children linked to a parent user account.
     */
    public function children(Request $request, $id)
    {
        $caller = $request->user();
        $parent = User::find($id);
        $role = $parent?->role instanceof UserRole
            ? $parent->role
            : UserRole::tryFromMixed($parent?->role);

        if (! $parent || $role !== UserRole::Parent) {
            return response()->json([
                'data' => [],
                'message' => 'Parent not found',
            ], 404);
        }

        if ($this->parentAccess->isParent($caller) && $caller->id !== $parent->id) {
            throw new AccessDeniedHttpException('You can only view your own linked children.');
        }

        if (! $this->parentAccess->isParent($caller) && ! $this->parentAccess->canManageAnyParent($caller)) {
            throw new AccessDeniedHttpException('You do not have permission to view parent children.');
        }

        $schoolId = $caller->school_id;
        $childIds = $this->parentAccess->accessibleStudentIds($parent);

        $children = \App\Models\Student::query()
            ->whereIn('id', $childIds)
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('status', 'active')
            ->with('classModel:id,name')
            ->get()
            ->map(function ($student) use ($parent) {
                $pivot = $parent->students()->where('students.id', $student->id)->first()?->pivot;

                return [
                    'id' => $student->id,
                    'firstName' => $student->first_name,
                    'surname' => $student->last_name,
                    'fullName' => $student->full_name,
                    'studentNumber' => $student->student_number,
                    'class' => $student->classModel?->name ?? $student->class,
                    'class_id' => $student->class_id,
                    'relationship' => $pivot?->relationship,
                    'is_primary' => (bool) ($pivot?->is_primary ?? false),
                    'dateOfBirth' => $student->date_of_birth,
                    'gender' => $student->gender,
                    'profileImage' => $student->profile_image ?? null,
                ];
            });

        return response()->json(['data' => $children]);
    }
}
