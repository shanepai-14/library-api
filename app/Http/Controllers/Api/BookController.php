<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Book;
class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['author', 'category']);

        // Search functionality
        if ($request->has('search')) {
            $searchTerm = $request->search;
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
        ]);

        $book = Book::create($validatedData);
        return response()->json($book, Response::HTTP_CREATED);
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
            'isbn' => 'nullable|unique:books,isbn,' . $book->id . '|max:13',
            'description' => 'nullable',
            'publication_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'publisher' => 'nullable|max:255',
            'language' => 'nullable|max:50',
            'book_price' => 'nullable|numeric|min:0',
        ]);

        $book->update($validatedData);
        return response()->json($book);
    }

    /**
     * Remove the specified book from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
