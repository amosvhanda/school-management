<?php

namespace App\Http\Controllers;

use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelBed;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Services\Domain\SchoolDomainRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HostelController extends Controller
{
    public function index(Request $request)
    {
        $query = Hostel::where('school_id', $request->user()->school_id)->with(['rooms.beds']);

        if ($request->filled('gender')) {
            $query->where('gender', $request->string('gender'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:male,female,mixed',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $hostel = Hostel::create([...$data, 'school_id' => $request->user()->school_id]);

        return response()->json(['data' => $hostel], 201);
    }

    public function update(Request $request, int $id)
    {
        $hostel = Hostel::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'gender' => 'nullable|string|in:male,female,mixed',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $hostel->update($data);

        return response()->json(['data' => $hostel->fresh(), 'message' => 'Hostel updated']);
    }

    public function storeRoom(Request $request, int $hostelId)
    {
        // Enforce tenancy immediately
        $hostel = Hostel::where('school_id', $request->user()->school_id)->findOrFail($hostelId);

        $data = $request->validate([
            'room_number' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'beds' => 'nullable|integer|min:1',
        ]);

        // Wrap multiple inserts in a transaction for data integrity
        $room = DB::transaction(function () use ($hostelId, $data) {
            $room = HostelRoom::create([
                'hostel_id' => $hostelId,
                'room_number' => $data['room_number'],
                'capacity' => $data['capacity'] ?? 1,
            ]);

            $bedCount = $data['beds'] ?? $room->capacity;

            // Optimization: Prepare bulk insert data instead of querying in a loop
            $bedsData = [];
            for ($i = 1; $i <= $bedCount; $i++) {
                $bedsData[] = [
                    'room_id' => $room->id,
                    'bed_number' => (string) $i,
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            HostelBed::insert($bedsData);

            return $room;
        });

        return response()->json(['data' => $room->load('beds')], 201);
    }

    public function allocate(Request $request)
    {
        $schoolId = $request->user()->school_id;

        // Custom validation to cleanly catch scope issues before executing business logic
        $data = $request->validate([
            'student_id' => 'required|integer',
            'bed_id' => 'required|integer',
        ]);

        // Verify ownership safely
        $student = Student::where('school_id', $schoolId)->findOrFail($data['student_id']);
        $bed = HostelBed::whereHas('room.hostel', function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->with('room')->findOrFail($data['bed_id']);

        app(SchoolDomainRules::class)->assertStudentActive($student);
        app(SchoolDomainRules::class)->assertHostelRoomHasCapacity($bed);

        // Prevent business logic failures (Double bookings)
        if ($bed->status !== 'available') {
            throw ValidationException::withMessages(['bed_id' => ['This bed is already occupied.']]);
        }

        $alreadyAllocated = HostelAllocation::where('student_id', $student->id)
            ->where('status', 'active')
            ->exists();

        if ($alreadyAllocated) {
            throw ValidationException::withMessages(['student_id' => ['This student already has an active hostel allocation.']]);
        }

        // Database transaction protects against partial failures
        $allocation = DB::transaction(function () use ($student, $bed) {
            $bed->update(['status' => 'occupied']);

            return HostelAllocation::create([
                'student_id' => $student->id,
                'bed_id' => $bed->id,
                'allocated_at' => now(),
                'status' => 'active',
                'school_id' => $student->school_id, // Recommended: add tenancy to allocations table too
            ]);
        });

        return response()->json(['data' => $allocation], 201);
    }
}
