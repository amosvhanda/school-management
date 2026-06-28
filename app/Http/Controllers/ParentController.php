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

        $children = $parent->students()
            ->when($schoolId, fn ($query) => $query->where('students.school_id', $schoolId))
            ->where('students.status', 'active')
            ->get()
            ->map(fn ($student) => [
                'id' => $student->id,
                'firstName' => $student->first_name,
                'surname' => $student->last_name,
                'fullName' => $student->full_name,
                'studentNumber' => $student->student_number,
                'class' => $student->classModel?->name ?? $student->class,
                'class_id' => $student->class_id,
                'relationship' => $student->pivot?->relationship,
                'is_primary' => (bool) $student->pivot?->is_primary,
                'dateOfBirth' => $student->date_of_birth,
                'gender' => $student->gender,
                'profileImage' => $student->profile_image ?? null,
            ]);

        return response()->json(['data' => $children]);
    }
}
