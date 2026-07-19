<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    private function authorizeReception(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            ['canManageReception'],
            ['reception.manage', 'operations.manage'],
        );
    }

    public function index(Request $request)
    {
        $this->authorizeReception($request);

        $query = Visitor::where('school_id', $request->user()->school_id)
            ->with(['host:id,name,email,role', 'student:id,first_name,last_name,student_number'])
            ->orderByDesc('check_in_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->limit(100)->get()]);
    }

    public function checkIn(Request $request)
    {
        $this->authorizeReception($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'id_number' => 'nullable|string|max:50',
            'purpose' => 'required|string|max:255',
            'host_user_id' => 'nullable|exists:users,id',
            'student_id' => 'nullable|exists:students,id',
        ]);

        $visitor = Visitor::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'check_in_at' => now(),
            'status' => 'checked_in',
        ]);

        return response()->json([
            'data' => $visitor->load(['host:id,name,email,role', 'student:id,first_name,last_name,student_number']),
            'message' => 'Visitor checked in',
        ], 201);
    }

    public function checkOut(Request $request, int $id)
    {
        $this->authorizeReception($request);

        $visitor = Visitor::where('school_id', $request->user()->school_id)->findOrFail($id);
        $visitor->update(['check_out_at' => now(), 'status' => 'checked_out']);

        return response()->json(['data' => $visitor]);
    }
}
