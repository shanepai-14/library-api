<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Book;
use App\Models\User;
use App\Models\BookLoan;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['author', 'category'])
        ->leftJoinSub(
            BookLoan::selectRaw('book_id, COUNT(*) as active_loans')
                ->whereNull('actual_return_date')
                ->groupBy('book_id'),
            'active_loans',
            'books.id',
            '=',
            'active_loans.book_id'
        )
        ->selectRaw('books.*, COALESCE(books.total_copies - IFNULL(active_loans.active_loans, 0), books.total_copies) as available_copies');

        // Search functionality
        if ($request->has('search')) {
            $searchTerm = $request->search == "all" ? "" : $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('isbn', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('author', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('category', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Pagination
        $perPage = $request->input('row', 15); // Default to 15 if not specified
        $page = $request->input('page', 1);

        $books = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $books->items(),
            'current_page' => $books->currentPage(),
            'per_page' => $books->perPage(),
            'total' => $books->total(),
            'last_page' => $books->lastPage(),
            'image_url' => asset('storage/'),
        ]);
    }
    /**
     * Store a newly created book in storage.
     */
    public function store(Request $request)
{
    $validatedData = $request->validate([
        'title' => 'required|max:255',
        'author_id' => 'required|exists:authors,id',
        'category_id' => 'required|exists:categories,id',
        'isbn' => 'nullable|unique:books,isbn|max:13',
        'description' => 'nullable',
        'publication_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
        'publisher' => 'nullable|max:255',
        'language' => 'nullable|max:50',
        'book_price' => 'nullable|numeric|min:0',
        'total_copies' => 'required|numeric|min:1',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048',
        'subject_ids' => 'nullable|array',
        'subject_ids.*' => 'exists:subjects,id'
    ]);

    try {
        DB::beginTransaction();

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('public/book_covers', $filename);
            $validatedData['image'] = str_replace('public/', '', $path);
        }

        // Remove subject_ids from validatedData as it's not a column in books table
        $subjectIds = $request->input('subject_ids', []);
        unset($validatedData['subject_ids']);

        // Create the book
        $book = Book::create($validatedData);

        // Attach subjects to the book
        if (!empty($subjectIds)) {
            $book->subjects()->attach($subjectIds);
        }

        DB::commit();

        // Load the relationships for the response
        $book->load(['author', 'category', 'subjects']);

        return response()->json([
            'message' => 'Book created successfully',
            'data' => $book
        ], Response::HTTP_CREATED);

    } catch (\Exception $e) {
        DB::rollBack();
        
        // Delete uploaded image if it exists
        if (isset($validatedData['image'])) {
            Storage::delete('public/' . $validatedData['image']);
        }

        return response()->json([
            'message' => 'Error creating book',
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
     * Display the specified book.
     */
    public function show(Book $book)
    {
        return response()->json($book->load(['author', 'category']));
    }

    /**
     * Update the specified book in storage.
     */
    public function update(Request $request, Book $book)
    {
        $validatedData = $request->validate([
            'title' => 'sometimes|required|max:255',
            'author_id' => 'sometimes|required|exists:authors,id',
            'category_id' => 'sometimes|required|exists:categories,id',
            'isbn' => 'nullable|max:13|unique:books,isbn,' . $book->id,
            'description' => 'nullable',
            'publication_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'publisher' => 'nullable|max:255',
            'language' => 'nullable|max:50',
            'book_price' => 'nullable|numeric|min:0',
            'total_copies' => 'required|numeric|min:1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048'
        ]);
    
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($book->image) {
                Storage::delete('public/' . $book->image);
            }
    
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('public/book_covers', $filename);
            $validatedData['image'] = str_replace('public/', '', $path);
        }
    
        $book->update($validatedData);
    
        return response()->json($book, Response::HTTP_OK);
    }

    public function getBooksByCategory(Request $request, $categoryId)
{
    $page = $request->input('page', 1);
    $rowsPerPage = $request->input('row', 10);
    $search = $request->input('search', '');

    $query = Book::where('category_id', $categoryId)
                 ->with(['author', 'category']);

    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhereHas('author', function ($authorQuery) use ($search) {
                  $authorQuery->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    $totalBooks = $query->count();
    
    $books = $query->orderBy('title')
                   ->skip(($page - 1) * $rowsPerPage)
                   ->take($rowsPerPage)
                   ->get();

    if ($books->isEmpty()) {
        return response()->json(['message' => 'No books found in this category'], 200);
    }

    $totalPages = ceil($totalBooks / $rowsPerPage);

    $response = [
        'data' => $books,
        'current_page' => $page,
        'rows_per_page' => $rowsPerPage,
        'total' => $totalBooks,
        'total' => $totalPages
    ];

    return response()->json($response, 200);
}
public function getBooksByAuthor(Request $request, $authorId)
{
    $page = $request->input('page', 1);
    $rowsPerPage = $request->input('row', 10);
    $search = $request->input('search', '');

    $query = Book::where('author_id', $authorId)
                 ->with(['author', 'category']);

    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhereHas('author', function ($authorQuery) use ($search) {
                  $authorQuery->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    $totalBooks = $query->count();
    
    $books = $query->orderBy('title')
                   ->skip(($page - 1) * $rowsPerPage)
                   ->take($rowsPerPage)
                   ->get();

    if ($books->isEmpty()) {
        return response()->json(['message' => 'No books found in this category'], 200);
    }

    $totalPages = ceil($totalBooks / $rowsPerPage);

    $response = [
        'data' => $books,
        'current_page' => $page,
        'rows_per_page' => $rowsPerPage,
        'total' => $totalBooks,
        'total' => $totalPages
    ];

    return response()->json($response, 200);
}

    /**
     * Remove the specified book from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function getRecommendedBooks($userId)
{
    $user = User::findOrFail($userId);

    if ($user->role !== 'student') {
        return response()->json([
            'message' => 'Recommendations are only available for students',
            'recommended_books' => []
        ]);
    }

    // Convert year level format
    $yearLevel = (int) preg_replace('/[^0-9]/', '', $user->year_level);
            
    // Get recommended books through the pivot table
    $recommendedBooks = Book::whereHas('subjects', function($query) use ($user, $yearLevel) {
            $query->where('year_level', $yearLevel)
                  ->where('department', $user->course);
        })
        ->with(['author', 'category', 'subjects'])
        ->select('books.*')
        ->selectRaw('
            (books.total_copies - (
                SELECT COUNT(*) 
                FROM book_loans 
                WHERE book_loans.book_id = books.id 
                AND book_loans.actual_return_date IS NULL
            )) as available_copies
        ')
        ->havingRaw('available_copies > 0')
        ->get();

    return response()->json([
        'message' => 'Recommended books retrieved successfully',
        'recommended_books' => $recommendedBooks
    ]);
}
}
