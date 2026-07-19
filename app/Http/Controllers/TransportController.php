<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Student;
use App\Models\StudentTransportAllocation;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    private function authorizeTransport(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            ['canManageTransport'],
            ['transport.manage', 'operations.manage'],
        );
    }

    public function vehicles(Request $request)
    {
        $this->authorizeTransport($request);

        return response()->json(['data' => Vehicle::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeVehicle(Request $request)
    {
        $this->authorizeTransport($request);

        $data = $request->validate([
            'registration_number' => 'required|string|max:50',
            'make' => 'nullable|string',
            'model' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $vehicle = Vehicle::create([...$data, 'school_id' => $request->user()->school_id]);

        return response()->json(['data' => $vehicle], 201);
    }

    public function drivers(Request $request)
    {
        $this->authorizeTransport($request);

        return response()->json(['data' => Driver::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeDriver(Request $request)
    {
        $this->authorizeTransport($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'license_number' => 'nullable|string',
            'phone' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $driver = Driver::create([...$data, 'school_id' => $request->user()->school_id]);

        return response()->json(['data' => $driver], 201);
    }

    public function routes(Request $request)
    {
        $this->authorizeTransport($request);

        return response()->json([
            'data' => TransportRoute::where('school_id', $request->user()->school_id)
                ->with(['vehicle', 'driver'])
                ->get(),
        ]);
    }

    public function storeRoute(Request $request)
    {
        $this->authorizeTransport($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'route_description' => 'nullable|string',
        ]);

        $route = TransportRoute::create([...$data, 'school_id' => $request->user()->school_id]);

        return response()->json(['data' => $route], 201);
    }

    public function allocateStudent(Request $request)
    {
        $this->authorizeTransport($request);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'route_id' => 'required|exists:transport_routes,id',
            'pickup_point' => 'nullable|string',
        ]);

        $schoolId = $request->user()->school_id;

        $student = Student::where('school_id', $schoolId)->findOrFail($data['student_id']);
        $route = TransportRoute::where('school_id', $schoolId)->with('vehicle')->findOrFail($data['route_id']);

        $domain = app(\App\Services\Domain\SchoolDomainRules::class);
        $domain->assertStudentActive($student);
        $domain->assertTransportRouteHasCapacity($route, ignoreStudentId: (int) $student->id);

        $allocation = StudentTransportAllocation::updateOrCreate(
            ['student_id' => $student->id, 'route_id' => $data['route_id']],
            ['pickup_point' => $data['pickup_point'] ?? null, 'status' => 'active', 'allocated_at' => now()],
        );

        return response()->json(['data' => $allocation], 201);
    }
}
