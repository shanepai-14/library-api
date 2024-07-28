<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Book;
use App\Models\BookLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;


class BookLoanController extends Controller
{
    public function index(Request $request)
    {
        $query = BookLoan::with(['user', 'book']);

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('id_number', 'LIKE', "%{$searchTerm}%");
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

    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'book_id' => 'required|exists:books,id',
    //         'due_date' => 'required|date|after:loan_date',
    //     ]);
    //     $validatedData['loan_date'] = Carbon::now()->toDateString();
    //     $bookLoan = BookLoan::create($validatedData);
    //     return response()->json($bookLoan, Response::HTTP_CREATED);
    // }

    public function store(Request $request)
{
    $validatedData = $request->validate([
        'user_id' => 'required|exists:users,id',
        'book_id' => 'required|exists:books,id',
        'due_date' => 'required|date|after:loan_date',
    ]);

    // Check if the book has available copies
    $book = Book::findOrFail($validatedData['book_id']);
    $availableCopies = $book->total_copies - BookLoan::where('book_id', $book->id)
                                              ->whereNull('actual_return_date')
                                              ->count();

    if ($availableCopies <= 0) {
        throw ValidationException::withMessages([
            'book_id' => ['No copies of this book are currently available.'],
        ]);
    }

    // If we reach here, there are available copies, so we can create the loan
    DB::beginTransaction();
    try {
        $validatedData['loan_date'] = Carbon::now()->toDateString();
        $bookLoan = BookLoan::create($validatedData);

        DB::commit();
        return response()->json($bookLoan, Response::HTTP_CREATED);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'An error occurred while creating the book loan.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    public function show(BookLoan $bookLoan)
    {
        return response()->json($bookLoan->load(['user', 'book']));
    }

    public function update(Request $request, BookLoan $bookLoan)
    {
        $validatedData = $request->validate([
            'return_date' => 'nullable|date|after:loan_date',
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
            'due_date' => 'required|date|after:loan_date',
        ]);

        $book = Book::findOrFail($validatedData['book_id']);
        $availableCopies = $book->total_copies - BookLoan::where('book_id', $book->id)
            ->whereNull('actual_return_date')
            ->count();

        if ($availableCopies <= 0) {
            throw ValidationException::withMessages([
                'book_id' => ['No copies of this book are currently available.'],
            ]);
        }
        DB::beginTransaction();
        try {
            $bookLoan->update($validatedData);
            DB::commit();
            return response()->json($bookLoan);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while creating the book loan.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(BookLoan $bookLoan)
    {
        $bookLoan->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function fetchActiveLoans($book_id)
{
    try {
        $activeLoans = BookLoan::where('book_id', $book_id)
                               ->whereNull('actual_return_date')
                               ->with(['user','book'])  // Assuming you want user details
                               ->get();
        if ($activeLoans->isEmpty()) {
            return response()->json([
                'message' => 'No active loans found for this book.',
                'data' => []
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Active loans retrieved successfully.',
            'data' => $activeLoans
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while fetching active loans.',
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function checkEligibleForReturn($bookLoanId)
{
    try {
        $bookLoan = BookLoan::with(['user', 'book'])->findOrFail($bookLoanId);

        $isEligible = is_null($bookLoan->actual_return_date);

        return response()->json([
            'is_eligible' => $isEligible,
            'message' => $isEligible 
                ? 'This book loan is eligible for return.' 
                : 'This book loan has already been returned.',
            'data' => $isEligible ? $bookLoan : null
        ], Response::HTTP_OK);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Book loan not found.',
            'is_eligible' => false
        ], Response::HTTP_NOT_FOUND);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while checking the book loan.',
            'error' => $e->getMessage(),
            'is_eligible' => false
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function returnBook($bookLoanId)
{
    try {
        $bookLoan = BookLoan::findOrFail($bookLoanId);

        if (!is_null($bookLoan->actual_return_date)) {
            return response()->json([
                'message' => 'This book has already been returned.',
                'data' => $bookLoan
            ], Response::HTTP_BAD_REQUEST);
        }

        $bookLoan->actual_return_date = now();
        $bookLoan->save();


        return response()->json([
            'message' => 'Book successfully returned.',
            'data' => $bookLoan
        ], Response::HTTP_OK);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Book loan not found.',
        ], Response::HTTP_NOT_FOUND);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while returning the book.',
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
}