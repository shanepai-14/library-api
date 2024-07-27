<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */

    public function allCategory(){
        $categories = Category::all();
        return response()->json($categories);
    }
    public function index(Request $request)
    {
        $query = Category::with('books');

        // Search functionality
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('books', function ($q) use ($searchTerm) {
                      $q->where('title', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Pagination
        $perPage = $request->input('row', 15); // Default to 15 if not specified
        $page = $request->input('page', 1);

        $categories = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $categories->items(),
            'current_page' => $categories->currentPage(),
            'per_page' => $categories->perPage(),
            'total' => $categories->total(),
            'last_page' => $categories->lastPage(),
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:categories|max:255',
            'description' => 'nullable',
            'status' => 'required',
        ]);

        $category = Category::create($validatedData);
        return response()->json($category, Response::HTTP_CREATED);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        return response()->json($category->load('books'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|unique:categories,name,' . $category->id . '|max:255',
            'description' => 'nullable',
            'status' => 'required'
        ]);

        $category->update($validatedData);
        return response()->json($category);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}