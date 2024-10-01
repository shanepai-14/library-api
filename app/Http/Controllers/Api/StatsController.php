<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Book;
use App\Models\Author;
use App\Models\FeaturePost;
use App\Models\BookLoan;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function getStats()
    {
        $stats = [
            [
                'title' => 'Students',
                'count' => User::where('role', 'student')->count(),
                'link' => '/admin/students',
                'bgColor' => '#3f51b5',
            ],
            [
                'title' => 'Attendance',
                'count' => Attendance::count(),
                'link' => '/admin/attendance',
                'bgColor' => '#f50057',
            ],
            [
                'title' => 'Books',
                'count' => Book::count(),
                'link' => '/admin/books',
                'bgColor' => '#ff9800',
            ],
            [
                'title' => 'Authors',
                'count' => Author::count(),
                'link' => '/admin/authors',
                'bgColor' => '#009688',
            ],
            [
                'title' => 'Post',
                'count' => FeaturePost::count(),
                'link' => '/admin/post',
                'bgColor' => '#9c27b0',
            ],
            [
                'title' => 'Issued Books',
                'count' => BookLoan::count(),
                'link' => '/admin/bookloans',
                'bgColor' => '#4caf50',
            ],
            [
                'title' => 'Category',
                'count' => Category::count(),
                'link' => '/admin/categories',
                'bgColor' => '#1976D2',
            ],
        ];

        return response()->json($stats);
    }

    public function getStudentStats()
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'student') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $totalBooksIssued = BookLoan::where('user_id', $user->id)->count();
        
        $booksNotReturned = BookLoan::where('user_id', $user->id)
                                    ->whereNull('actual_return_date')
                                    ->count();

        $stats = [
            [
                'title' => 'Issued Books',
                'count' => $totalBooksIssued,
                'bgColor' => '#4caf50',
                'link' => '/student/issued-books'
            ],
            [
                'title' => 'Pending Returns',
                'count' => $booksNotReturned,
                'bgColor' => '#f44336',
                'link' => '/student/issued-books'
            ]
        ];

        return response()->json($stats);
    }
}