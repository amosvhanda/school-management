<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPaginatedList;
use App\Models\HelpDeskTicket;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class HelpDeskController extends Controller
{
    use RespondsWithPaginatedList;

    private function authorizeHelpDesk(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageReception', 'canManageTeachers', 'canManageStudents'],
            permissionSlugs: ['reception.manage', 'operations.manage', 'students.manage'],
        );
    }

    private function nextTicketNumber(int $schoolId): string
    {
        $year = now()->format('Y');
        $count = HelpDeskTicket::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('TKT-%s-%05d', $year, $count + 1);
    }

    private function ticketQuery(int $schoolId)
    {
        return HelpDeskTicket::where('school_id', $schoolId)
            ->with([
                'student:id,full_name,student_number',
                'assignedTo:id,name,email',
                'creator:id,name,email',
            ]);
    }

    public function index(Request $request)
    {
        $this->authorizeHelpDesk($request);
        $schoolId = $request->user()->school_id;

        $query = $this->ticketQuery($schoolId)->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $this->indexResponse($request, $query);
    }

    public function store(Request $request)
    {
        $this->authorizeHelpDesk($request);
        $schoolId = $request->user()->school_id;

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:general,it,facilities,finance,academic,transport,other',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'nullable|email|max:255',
            'requester_phone' => 'nullable|string|max:30',
            'student_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
        ]);

        if (! empty($data['student_id'])) {
            Student::where('school_id', $schoolId)->findOrFail($data['student_id']);
        }

        if (! empty($data['assigned_to'])) {
            User::where('school_id', $schoolId)->findOrFail($data['assigned_to']);
        }

        $ticket = HelpDeskTicket::create([
            ...$data,
            'school_id' => $schoolId,
            'ticket_number' => $this->nextTicketNumber($schoolId),
            'category' => $data['category'] ?? 'general',
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $ticket->load([
                'student:id,full_name,student_number',
                'assignedTo:id,name,email',
                'creator:id,name,email',
            ]),
            'message' => 'Support ticket created',
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeHelpDesk($request);
        $schoolId = $request->user()->school_id;
        $ticket = HelpDeskTicket::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|in:general,it,facilities,finance,academic,transport,other',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'status' => 'sometimes|string|in:open,in_progress,resolved,closed',
            'requester_name' => 'sometimes|string|max:255',
            'requester_email' => 'nullable|email|max:255',
            'requester_phone' => 'nullable|string|max:30',
            'student_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
        ]);

        if (array_key_exists('student_id', $data) && $data['student_id']) {
            Student::where('school_id', $schoolId)->findOrFail($data['student_id']);
        }

        if (array_key_exists('assigned_to', $data) && $data['assigned_to']) {
            User::where('school_id', $schoolId)->findOrFail($data['assigned_to']);
        }

        if (isset($data['status'])) {
            if ($data['status'] === 'resolved' && ! $ticket->resolved_at) {
                $data['resolved_at'] = now();
            }
            if ($data['status'] === 'closed' && ! $ticket->closed_at) {
                $data['closed_at'] = now();
            }
            if ($data['status'] === 'open') {
                $data['resolved_at'] = null;
                $data['closed_at'] = null;
            }
        }

        $ticket->update($data);

        return response()->json([
            'data' => $ticket->fresh()->load([
                'student:id,full_name,student_number',
                'assignedTo:id,name,email',
                'creator:id,name,email',
            ]),
            'message' => 'Ticket updated',
        ]);
    }

    public function resolve(Request $request, int $id)
    {
        $this->authorizeHelpDesk($request);
        $ticket = HelpDeskTicket::where('school_id', $request->user()->school_id)->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'Closed tickets cannot be resolved.'], 422);
        }

        $ticket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json([
            'data' => $ticket->fresh()->load([
                'student:id,full_name,student_number',
                'assignedTo:id,name,email',
                'creator:id,name,email',
            ]),
            'message' => 'Ticket resolved',
        ]);
    }

    public function close(Request $request, int $id)
    {
        $this->authorizeHelpDesk($request);
        $ticket = HelpDeskTicket::where('school_id', $request->user()->school_id)->findOrFail($id);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'data' => $ticket->fresh()->load([
                'student:id,full_name,student_number',
                'assignedTo:id,name,email',
                'creator:id,name,email',
            ]),
            'message' => 'Ticket closed',
        ]);
    }
}
