<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $query = Visitor::where('school_id', $request->user()->school_id)->orderByDesc('check_in_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->limit(100)->get()]);
    }

    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'id_number' => 'nullable|string',
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

        return response()->json(['data' => $visitor], 201);
    }

    public function checkOut(Request $request, int $id)
    {
        $visitor = Visitor::where('school_id', $request->user()->school_id)->findOrFail($id);
        $visitor->update(['check_out_at' => now(), 'status' => 'checked_out']);

        return response()->json(['data' => $visitor]);
    }
}
