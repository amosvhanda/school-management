<?php

namespace App\Http\Controllers;

use App\Models\SchoolEvent;
use Illuminate\Http\Request;

class SchoolEventController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => SchoolEvent::where('school_id', $request->user()->school_id)
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $event = SchoolEvent::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $event], 201);
    }
}
