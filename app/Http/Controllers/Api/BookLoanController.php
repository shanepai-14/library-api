<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookLoanController extends Controller
{
    public function index(Request $request)
    {
        $query = BookLoan::with(['user', 'book']);

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%");
                })->orWhereHas('book', function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%");
                });
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'overdue') {
                $query->overdue();
            } elseif ($request->status === 'active') {
                $query->active();
            }
        }

        $perPage = $request->input('row', 15);
        $page = $request->input('page', 1);

        $bookLoans = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $bookLoans->items(),
            'current_page' => $bookLoans->currentPage(),
            'per_page' => $bookLoans->perPage(),
            'total' => $bookLoans->total(),
            'last_page' => $bookLoans->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
            'loan_date' => 'required|date',
            'due_date' => 'required|date|after:loan_date',
        ]);

        $bookLoan = BookLoan::create($validatedData);
        return response()->json($bookLoan, Response::HTTP_CREATED);
    }

    public function show(BookLoan $bookLoan)
    {
        return response()->json($bookLoan->load(['user', 'book']));
    }

    public function update(Request $request, BookLoan $bookLoan)
    {
        $validatedData = $request->validate([
            'return_date' => 'required|date|after:loan_date',
        ]);

        $bookLoan->update($validatedData);
        return response()->json($bookLoan);
    }

    public function destroy(BookLoan $bookLoan)
    {
        $bookLoan->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}