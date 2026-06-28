<?php

namespace App\Http\Controllers;

use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\Student;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function books(Request $request)
    {
        return response()->json(['data' => LibraryBook::where('school_id', $request->user()->school_id)->orderBy('title')->get()]);
    }

    public function storeBook(Request $request)
    {
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

    public function borrow(Request $request)
    {
        $data = $request->validate([
            'book_id' => 'required|exists:library_books,id',
            'student_id' => 'required|exists:students,id',
            'due_at' => 'required|date|after:today',
        ]);

        $book = LibraryBook::where('school_id', $request->user()->school_id)->findOrFail($data['book_id']);
        $student = Student::where('school_id', $request->user()->school_id)->findOrFail($data['student_id']);

        if ($book->available_copies < 1) {
            return response()->json(['message' => 'No copies available'], 422);
        }

        $loan = LibraryLoan::create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'borrowed_at' => now(),
            'due_at' => $data['due_at'],
            'status' => 'borrowed',
        ]);

        $book->decrement('available_copies');

        return response()->json(['data' => $loan], 201);
    }

    public function returnBook(Request $request, int $loanId)
    {
        $loan = LibraryLoan::with('book')->findOrFail($loanId);
        $book = LibraryBook::where('school_id', $request->user()->school_id)->findOrFail($loan->book_id);

        $fine = 0;
        if (now()->gt($loan->due_at)) {
            $fine = now()->diffInDays($loan->due_at) * 1.0;
        }

        $loan->update(['returned_at' => now(), 'status' => 'returned', 'fine_amount' => $fine]);
        $book->increment('available_copies');

        return response()->json(['data' => $loan->fresh(), 'fine' => $fine]);
    }
}
