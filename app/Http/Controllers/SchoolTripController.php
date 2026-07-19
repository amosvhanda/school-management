<?php

namespace App\Http\Controllers;

use App\Models\SchoolTrip;
use App\Models\SchoolTripEnrollment;
use App\Models\Student;
use App\Services\SchoolTripService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SchoolTripController extends Controller
{
    public function __construct(private SchoolTripService $tripService) {}

    public function index(Request $request)
    {
        $trips = SchoolTrip::where('school_id', $request->user()->school_id)
            ->withCount(['enrollments as enrolled_count' => fn ($q) => $q->where('status', 'enrolled')])
            ->orderByDesc('trip_date')
            ->get()
            ->map(function (SchoolTrip $trip) {
                $data = $trip->toArray();
                $data['spots_remaining'] = $trip->capacity === null
                    ? null
                    : max(0, (int) $trip->capacity - (int) $trip->enrolled_count);

                return $data;
            });

        return response()->json(['data' => $trips]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'destination' => 'nullable|string|max:255',
            'trip_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:trip_date',
            'fee_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'open_for_registration' => 'nullable|boolean',
        ]);

        $trip = SchoolTrip::create([
            'school_id' => $request->user()->school_id,
            ...$data,
            'currency' => $data['currency'] ?? ($request->user()->school?->getDefaultCurrency() ?? 'USD'),
            'fee_amount' => $data['fee_amount'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'open_for_registration' => $data['open_for_registration'] ?? true,
        ]);

        return response()->json(['data' => $trip, 'message' => 'School trip created'], 201);
    }

    public function update(Request $request, int $id)
    {
        $trip = SchoolTrip::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'destination' => 'nullable|string|max:255',
            'trip_date' => 'sometimes|date',
            'return_date' => 'nullable|date',
            'fee_amount' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'open_for_registration' => 'sometimes|boolean',
        ]);

        $tripDate = $data['trip_date'] ?? $trip->trip_date?->toDateString();
        $returnDate = array_key_exists('return_date', $data) ? $data['return_date'] : $trip->return_date?->toDateString();
        if ($returnDate && $tripDate && strtotime((string) $returnDate) < strtotime((string) $tripDate)) {
            throw ValidationException::withMessages([
                'return_date' => ['Return date must be on or after the trip date.'],
            ]);
        }

        $trip->update($data);

        return response()->json(['data' => $trip->fresh(), 'message' => 'Trip updated']);
    }

    public function enrollments(Request $request, int $id)
    {
        $trip = SchoolTrip::where('school_id', $request->user()->school_id)->findOrFail($id);
        $enrollments = SchoolTripEnrollment::where('school_trip_id', $trip->id)
            ->with(['student:id,full_name,student_number', 'invoice:id,invoice_number,amount,balance,status'])
            ->orderByDesc('enrolled_at')
            ->get();

        return response()->json(['data' => $enrollments]);
    }

    public function enroll(Request $request, int $id)
    {
        $schoolId = $request->user()->school_id;
        $trip = SchoolTrip::where('school_id', $schoolId)->findOrFail($id);
        $data = $request->validate(['student_id' => 'required|integer']);

        Student::where('school_id', $schoolId)->findOrFail($data['student_id']);

        $enrollment = $this->tripService->enrollStudent($trip, (int) $data['student_id'], $request->user()->id);

        return response()->json([
            'data' => $enrollment->load(['student', 'invoice']),
            'message' => 'Student enrolled for the trip'
                .((float) $trip->fee_amount > 0 ? ' and invoiced' : ''),
        ], 201);
    }
}
