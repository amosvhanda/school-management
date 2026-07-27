<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPaginatedList;
use App\Models\DisciplinaryRecord;
use App\Models\Student;
use App\Services\ParentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisciplinaryRecordController extends Controller
{
    use RespondsWithPaginatedList;

    public function __construct(private ParentNotificationService $notifications) {}

    public function index(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $query = DisciplinaryRecord::query()
            ->with(['student:id,full_name,student_number,class_id', 'recorder:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('incident_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('action_taken', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%");
                    });
            });
        }

        return $this->indexResponse($request, $query->orderByDesc('incident_date'));
    }

    public function show(Request $request, int $id)
    {
        $schoolId = $request->user()?->school_id;

        $record = DisciplinaryRecord::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student', 'recorder:id,name'])
            ->findOrFail($id);

        return response()->json(['data' => $record]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'incident_date' => 'required|date',
            'category' => 'required|string|max:255',
            'severity' => 'nullable|string|in:minor,moderate,major',
            'description' => 'required|string',
            'action_taken' => 'nullable|string',
            'notify_parents' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $student = Student::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->findOrFail($request->student_id);

        $record = DisciplinaryRecord::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'incident_date' => $request->incident_date,
            'category' => $request->category,
            'severity' => $request->severity ?? 'minor',
            'description' => $request->description,
            'action_taken' => $request->action_taken,
            'recorded_by' => $user->id,
        ]);

        if ($request->boolean('notify_parents', true)) {
            $this->notifications->notifyDisciplinaryNotice($record);
        }

        return response()->json(['data' => $record->fresh()->load(['student', 'recorder'])], 201);
    }

    public function update(Request $request, int $id)
    {
        $schoolId = $request->user()?->school_id;

        $record = DisciplinaryRecord::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'incident_date' => 'sometimes|date',
            'category' => 'sometimes|string|max:255',
            'severity' => 'nullable|string|in:minor,moderate,major',
            'description' => 'sometimes|string',
            'action_taken' => 'nullable|string',
            'notify_parents' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $record->update($validator->validated());

        if ($request->boolean('notify_parents', false)) {
            $this->notifications->notifyDisciplinaryNotice($record);
        }

        return response()->json(['data' => $record->fresh()->load(['student', 'recorder']), 'message' => 'Disciplinary record updated']);
    }
}
