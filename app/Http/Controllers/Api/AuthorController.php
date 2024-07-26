<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthorController extends Controller
{
    /**
     * Display a listing of the authors.
     */
    public function index(Request $request)
    {
        $query = Author::with('books');

        // Search functionality
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('nationality', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('books', function ($q) use ($searchTerm) {
                      $q->where('title', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Pagination
        $perPage = $request->input('row', 15); // Default to 15 if not specified
        $page = $request->input('page', 1);

        $authors = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $authors->items(),
            'current_page' => $authors->currentPage(),
            'per_page' => $authors->perPage(),
            'total' => $authors->total(),
            'last_page' => $authors->lastPage(),
        ]);
    }

    /**
     * Store a newly created author in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'biography' => 'nullable',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|max:100',
        ]);

        $author = Author::create($validatedData);
        return response()->json($author, Response::HTTP_CREATED);
    }

    /**
     * Display the specified author.
     */
    public function show(Author $author)
    {
        return response()->json($author->load('books'));
    }

    /**
     * Update the specified author in storage.
     */
    public function update(Request $request, Author $author)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|max:255',
            'biography' => 'nullable',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|max:100',
        ]);

        $author->update($validatedData);
        return response()->json($author);
    }

    /**
     * Remove the specified author from storage.
     */
    public function destroy(Author $author)
    {
        $author->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}