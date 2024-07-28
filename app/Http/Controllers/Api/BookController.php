<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048'
        ]);
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('public/book_covers', $filename);
            $validatedData['image'] = str_replace('public/', '', $path);
        }

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
}
