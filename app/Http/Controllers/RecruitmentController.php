<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPaginatedList;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    use RespondsWithPaginatedList;

    private function authorizeRecruitment(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['hr.manage'],
        );
    }

    public function jobs(Request $request)
    {
        $this->authorizeRecruitment($request);
        $schoolId = $request->user()->school_id;

        $query = JobPosting::where('school_id', $schoolId)
            ->withCount('applications')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $this->indexResponse($request, $query);
    }

    public function storeJob(Request $request)
    {
        $this->authorizeRecruitment($request);
        $schoolId = $request->user()->school_id;

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract,temporary',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'openings' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:draft,open,closed',
            'closes_at' => 'nullable|date',
        ]);

        $status = $data['status'] ?? 'draft';

        $job = JobPosting::create([
            ...$data,
            'school_id' => $schoolId,
            'status' => $status,
            'openings' => $data['openings'] ?? 1,
            'published_at' => $status === 'open' ? now() : null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $job->loadCount('applications'),
            'message' => 'Job posting created',
        ], 201);
    }

    public function updateJob(Request $request, int $id)
    {
        $this->authorizeRecruitment($request);
        $schoolId = $request->user()->school_id;
        $job = JobPosting::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'sometimes|string|in:full_time,part_time,contract,temporary',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'openings' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|string|in:draft,open,closed',
            'closes_at' => 'nullable|date',
        ]);

        if (isset($data['status']) && $data['status'] === 'open' && ! $job->published_at) {
            $data['published_at'] = now();
        }

        $job->update($data);

        return response()->json([
            'data' => $job->fresh()->loadCount('applications'),
            'message' => 'Job posting updated',
        ]);
    }

    public function closeJob(Request $request, int $id)
    {
        $this->authorizeRecruitment($request);
        $job = JobPosting::where('school_id', $request->user()->school_id)->findOrFail($id);

        $job->update(['status' => 'closed']);

        return response()->json([
            'data' => $job->fresh()->loadCount('applications'),
            'message' => 'Job posting closed',
        ]);
    }

    public function applications(Request $request)
    {
        $this->authorizeRecruitment($request);
        $schoolId = $request->user()->school_id;

        $query = JobApplication::where('school_id', $schoolId)
            ->with([
                'jobPosting:id,title,department,status',
                'reviewer:id,name,email',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('job_posting_id')) {
            $query->where('job_posting_id', $request->integer('job_posting_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('applicant_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('jobPosting', fn ($jq) => $jq->where('title', 'like', "%{$search}%"));
            });
        }

        return $this->indexResponse($request, $query);
    }

    public function storeApplication(Request $request)
    {
        $this->authorizeRecruitment($request);
        $schoolId = $request->user()->school_id;

        $data = $request->validate([
            'job_posting_id' => 'required|integer',
            'applicant_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'resume_url' => 'nullable|string|max:2048',
            'cover_letter' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        JobPosting::where('school_id', $schoolId)
            ->where('status', 'open')
            ->findOrFail($data['job_posting_id']);

        $application = JobApplication::create([
            ...$data,
            'school_id' => $schoolId,
            'status' => 'submitted',
        ]);

        return response()->json([
            'data' => $application->load([
                'jobPosting:id,title,department,status',
                'reviewer:id,name,email',
            ]),
            'message' => 'Application recorded',
        ], 201);
    }

    public function updateApplication(Request $request, int $id)
    {
        $this->authorizeRecruitment($request);
        $schoolId = $request->user()->school_id;
        $application = JobApplication::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'applicant_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'resume_url' => 'nullable|string|max:2048',
            'cover_letter' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|string|in:submitted,screening,interview,offered,hired,rejected',
        ]);

        if (isset($data['status'])) {
            $data['reviewed_by'] = $request->user()->id;
            $data['reviewed_at'] = now();
        }

        $application->update($data);

        return response()->json([
            'data' => $application->fresh()->load([
                'jobPosting:id,title,department,status',
                'reviewer:id,name,email',
            ]),
            'message' => 'Application updated',
        ]);
    }

    public function shortlistApplication(Request $request, int $id)
    {
        return $this->advanceApplication($request, $id, 'shortlist');
    }

    public function interviewApplication(Request $request, int $id)
    {
        return $this->advanceApplication($request, $id, 'interview');
    }

    public function offerApplication(Request $request, int $id)
    {
        return $this->advanceApplication($request, $id, 'offer');
    }

    public function hireApplication(Request $request, int $id)
    {
        return $this->advanceApplication($request, $id, 'hire');
    }

    public function rejectApplication(Request $request, int $id)
    {
        return $this->advanceApplication($request, $id, 'reject');
    }

    public function advanceApplication(Request $request, int $id, string $stage)
    {
        $this->authorizeRecruitment($request);
        $application = JobApplication::where('school_id', $request->user()->school_id)->findOrFail($id);

        $nextStatus = match ($stage) {
            'shortlist' => 'screening',
            'interview' => 'interview',
            'offer' => 'offered',
            'hire' => 'hired',
            'reject' => 'rejected',
            default => abort(404),
        };

        if ($stage === 'reject' && in_array($application->status, ['hired', 'rejected'], true)) {
            return response()->json(['message' => 'Application is already closed.'], 422);
        }

        if ($stage !== 'reject') {
            $expected = match ($stage) {
                'shortlist' => 'submitted',
                'interview' => 'screening',
                'offer' => 'interview',
                'hire' => 'offered',
                default => null,
            };

            if ($application->status !== $expected) {
                return response()->json(['message' => "Application must be in {$expected} status."], 422);
            }
        }

        $application->update([
            'status' => $nextStatus,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'data' => $application->fresh()->load([
                'jobPosting:id,title,department,status',
                'reviewer:id,name,email',
            ]),
            'message' => 'Application updated',
        ]);
    }
}
