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

        $vehicles = Vehicle::where('school_id', $request->user()->school_id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('registration_number')
            ->get();

        return response()->json(['data' => $vehicles]);
    }

    public function storeVehicle(Request $request)
    {
        $this->authorizeTransport($request);

        $data = $request->validate([
            'registration_number' => 'required|string|max:50',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);

        $vehicle = Vehicle::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json(['data' => $vehicle, 'message' => 'Vehicle created'], 201);
    }

    public function updateVehicle(Request $request, int $id)
    {
        $this->authorizeTransport($request);

        $vehicle = Vehicle::where('school_id', $request->user()->school_id)->findOrFail($id);
        $vehicle->update($request->validate([
            'registration_number' => 'sometimes|string|max:50',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,maintenance',
        ]));

        return response()->json(['data' => $vehicle->fresh(), 'message' => 'Vehicle updated']);
    }

    public function drivers(Request $request)
    {
        $this->authorizeTransport($request);

        $drivers = Driver::where('school_id', $request->user()->school_id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $drivers]);
    }

    public function storeDriver(Request $request)
    {
        $this->authorizeTransport($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $driver = Driver::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json(['data' => $driver, 'message' => 'Driver created'], 201);
    }

    public function updateDriver(Request $request, int $id)
    {
        $this->authorizeTransport($request);

        $driver = Driver::where('school_id', $request->user()->school_id)->findOrFail($id);
        $driver->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:active,inactive',
        ]));

        return response()->json(['data' => $driver->fresh(), 'message' => 'Driver updated']);
    }

    public function routes(Request $request)
    {
        $this->authorizeTransport($request);

        $routes = TransportRoute::where('school_id', $request->user()->school_id)
            ->with(['vehicle', 'driver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $routes]);
    }

    public function storeRoute(Request $request)
    {
        $this->authorizeTransport($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'route_description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $route = TransportRoute::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json([
            'data' => $route->load(['vehicle', 'driver']),
            'message' => 'Route created',
        ], 201);
    }

    public function updateRoute(Request $request, int $id)
    {
        $this->authorizeTransport($request);

        $route = TransportRoute::where('school_id', $request->user()->school_id)->findOrFail($id);
        $route->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'route_description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]));

        return response()->json([
            'data' => $route->fresh()->load(['vehicle', 'driver']),
            'message' => 'Route updated',
        ]);
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
