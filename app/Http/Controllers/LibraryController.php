<?php

namespace App\Http\Controllers;

use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\LibraryMember;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LibraryController extends Controller
{
    private function authorizeLibrary(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            ['canManageLibrary'],
            ['library.manage', 'operations.manage'],
        );
    }

    public function books(Request $request)
    {
        $this->authorizeLibrary($request);

        $query = LibraryBook::where('school_id', $request->user()->school_id);

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return response()->json(['data' => $query->orderBy('title')->get()]);
    }

    public function storeBook(Request $request)
    {
        $this->authorizeLibrary($request);

        $data = $request->validate([
            'isbn' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'total_copies' => 'nullable|integer|min:1',
            'category' => 'nullable|string',
        ]);

        $copies = $data['total_copies'] ?? 1;
        $book = LibraryBook::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'total_copies' => $copies,
            'available_copies' => $copies,
        ]);

        return response()->json(['data' => $book], 201);
    }

    public function updateBook(Request $request, int $id)
    {
        $this->authorizeLibrary($request);

        $book = LibraryBook::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'isbn' => 'nullable|string|max:50',
            'title' => 'sometimes|string|max:255',
            'author' => 'nullable|string|max:255',
            'total_copies' => 'nullable|integer|min:1',
            'category' => 'nullable|string',
        ]);

        if (array_key_exists('total_copies', $data) && $data['total_copies'] !== null) {
            $delta = (int) $data['total_copies'] - (int) $book->total_copies;
            $data['available_copies'] = max(0, (int) $book->available_copies + $delta);
        }

        $book->update($data);

        return response()->json(['data' => $book->fresh(), 'message' => 'Book updated']);
    }

    public function destroyBook(Request $request, int $id)
    {
        $this->authorizeLibrary($request);

        $schoolId = (int) $request->user()->school_id;
        $book = LibraryBook::where('school_id', $schoolId)->findOrFail($id);

        $activeLoans = LibraryLoan::query()
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->exists();

        if ($activeLoans) {
            return response()->json([
                'message' => 'Return all borrowed copies before deleting this book.',
            ], 422);
        }

        $book->delete();

        return response()->json(['message' => 'Book deleted']);
    }

    public function loans(Request $request)
    {
        $this->authorizeLibrary($request);

        $schoolId = $request->user()->school_id;

        $query = LibraryLoan::query()
            ->with([
                'book:id,title,isbn,school_id',
                'student:id,first_name,last_name',
                'member:id,name,member_number,member_type,status',
            ])
            ->whereHas('book', fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('book_id')) {
            $query->where('book_id', (int) $request->book_id);
        }

        if ($request->filled('library_member_id')) {
            $query->where('library_member_id', (int) $request->library_member_id);
        }

        return response()->json([
            'data' => $query->orderByDesc('borrowed_at')->orderByDesc('id')->get(),
        ]);
    }

    public function borrow(Request $request)
    {
        $this->authorizeLibrary($request);

        $schoolId = (int) $request->user()->school_id;

        $data = $request->validate([
            'book_id' => [
                'required',
                'integer',
                Rule::exists('library_books', 'id')->where('school_id', $schoolId),
            ],
            'library_member_id' => [
                'nullable',
                'integer',
                Rule::exists('library_members', 'id')->where(function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)->where('status', 'active');
                }),
            ],
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists('students', 'id')->where('school_id', $schoolId),
            ],
            'due_at' => 'required|date|after_or_equal:today',
        ]);

        if (empty($data['library_member_id']) && empty($data['student_id'])) {
            throw ValidationException::withMessages([
                'library_member_id' => ['Select a library member or a student.'],
            ]);
        }

        $loan = DB::transaction(function () use ($data, $schoolId) {
            $book = LibraryBook::query()
                ->where('school_id', $schoolId)
                ->whereKey($data['book_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($book->available_copies < 1) {
                throw ValidationException::withMessages([
                    'book_id' => ['No copies available'],
                ]);
            }

            $memberId = isset($data['library_member_id']) ? (int) $data['library_member_id'] : null;
            $studentId = isset($data['student_id']) ? (int) $data['student_id'] : null;

            if ($memberId) {
                $member = LibraryMember::query()
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->findOrFail($memberId);

                if ($member->member_type === 'student' && $member->member_id) {
                    $studentId = (int) $member->member_id;
                    Student::query()
                        ->where('school_id', $schoolId)
                        ->whereKey($studentId)
                        ->firstOrFail();
                }
            } elseif ($studentId) {
                Student::query()
                    ->where('school_id', $schoolId)
                    ->whereKey($studentId)
                    ->firstOrFail();
            }

            $loan = LibraryLoan::create([
                'book_id' => $book->id,
                'library_member_id' => $memberId,
                'student_id' => $studentId,
                'borrowed_at' => now(),
                'due_at' => $data['due_at'],
                'status' => 'borrowed',
            ]);

            $book->decrement('available_copies');

            return $loan->load(['book', 'student', 'member']);
        });

        return response()->json(['data' => $loan], 201);
    }

    public function returnBook(Request $request, int $loanId)
    {
        $this->authorizeLibrary($request);

        $schoolId = (int) $request->user()->school_id;

        $result = DB::transaction(function () use ($loanId, $schoolId) {
            $loan = LibraryLoan::query()
                ->whereKey($loanId)
                ->whereHas('book', fn ($q) => $q->where('school_id', $schoolId))
                ->lockForUpdate()
                ->firstOrFail();

            $book = LibraryBook::query()
                ->where('school_id', $schoolId)
                ->whereKey($loan->book_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($loan->status !== 'borrowed') {
                throw ValidationException::withMessages([
                    'loan' => ['This loan has already been returned.'],
                ]);
            }

            $fine = 0;
            if (now()->gt($loan->due_at)) {
                $fine = now()->diffInDays($loan->due_at) * 1.0;
            }

            $loan->update([
                'returned_at' => now(),
                'status' => 'returned',
                'fine_amount' => $fine,
            ]);
            $book->increment('available_copies');

            return ['loan' => $loan->fresh(['book', 'student', 'member']), 'fine' => $fine];
        });

        return response()->json(['data' => $result['loan'], 'fine' => $result['fine']]);
    }
}
