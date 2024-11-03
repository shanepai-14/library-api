<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Http\Requests\SubjectRequest;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $row = $request->get('row', 5);
        $search = $request->get('search', '');
        
        $query = Subject::query();
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('department', 'LIKE', "%{$search}%");
            });
        }
        
        $subjects = $query->paginate($row);
        
        return response()->json($subjects);
    }

    public function store(SubjectRequest $request)
    {
        $subject = Subject::create($request->validated());
        
        return response()->json([
            'message' => 'Subject created successfully',
            'data' => $subject
        ], 201);
    }

    public function show($id)
    {
        $subject = Subject::findOrFail($id);
        return response()->json($subject);
    }

    public function update(SubjectRequest $request, $id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update($request->validated());
        
        return response()->json([
            'message' => 'Subject updated successfully',
            'data' => $subject
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();
        
        return response()->json([
            'message' => 'Subject deleted successfully'
        ]);
    }

    // Additional method to get books related to a subject
    public function books($id)
    {
        $subject = Subject::findOrFail($id);
        $books = $subject->books()
            ->paginate(10);
        
        return response()->json($books);
    }

    public function getAll()
{
    $subjects = Subject::select('id', 'code', 'name', 'description', 'year_level', 'department', 'semester')
                      ->orderBy('department')
                      ->orderBy('year_level')
                      ->orderBy('semester')
                      ->get();
                      
    return response()->json($subjects);
}
}