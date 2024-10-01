<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeaturePost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeaturePostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $featurePosts = FeaturePost::with('author')->latest()->get();
        return response()->json($featurePosts);
    }
    public function latest()
    {
        $latestPost = FeaturePost::with('author')->latest()->first(); // Get the latest post
        return response()->json($latestPost); // Return the latest post as JSON
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $featurePost = FeaturePost::create([
            'title' => $request->title,
            'content' => $request->content,
            'author_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Feature post created successfully',
            'feature_post' => $featurePost
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FeaturePost $featurePost)
    {
        $featurePost->load('author');
        return response()->json($featurePost);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeaturePost $featurePost)
    {
        // Check if the authenticated user is the author
        if ($featurePost->author_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $featurePost->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Feature post updated successfully',
            'feature_post' => $featurePost
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeaturePost $featurePost)
    {
        // Check if the authenticated user is the author
        if ($featurePost->author_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $featurePost->delete();

        return response()->json([
            'message' => 'Feature post deleted successfully'
        ]);
    }
}