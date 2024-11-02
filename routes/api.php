<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\BookLoanController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FeaturePostController;
use App\Http\Controllers\Api\StatsController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/check-student', [UserController::class, 'checkStudent']);
Route::post('/attendance/check', [AttendanceController::class, 'checkInOut']);
Route::get('/latest-post', [FeaturePostController::class, 'latest']);
Route::group(['middleware' => ['auth:sanctum']] ,function () {

    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user_update/{user}', [UserController::class, 'update']);
    Route::apiResource('user', UserController::class);
    Route::apiResource('books', BookController::class);
    Route::apiResource('authors', AuthorController::class);
    Route::apiResource('attendances', AttendanceController::class); 

    Route::get('/attendance/analytics', [AttendanceController::class, 'getAnalytics']);

    Route::prefix('attendance/analytics')->group(function () {
        Route::get('/daily', [AttendanceController::class, 'getDailyAnalytics']);
        Route::get('/weekly', [AttendanceController::class, 'getWeeklyAnalytics']);
        Route::get('/monthly', [AttendanceController::class, 'getMonthlyAnalytics']);
    });

    Route::apiResource('book-loans', BookLoanController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('feature-posts', FeaturePostController::class);
    Route::get('/books/category/{categoryId}', [BookController::class, 'getBooksByCategory']);
    Route::get('/books/author/{authorId}', [BookController::class, 'getBooksByAuthor']);
    Route::get('/author/all', [AuthorController::class, 'allAuthor']);
    Route::get('/category/all', [CategoryController::class, 'allCategory']);
    Route::get('/books/{book_id}/active-loans', [BookLoanController::class, 'fetchActiveLoans']);
    Route::get('/book-loans/{id}/return', [BookLoanController::class, 'checkEligibleForReturn']);
    Route::post('/book-loans/{bookLoanId}/return', [BookLoanController::class, 'returnBook']);
    Route::get('/admin/stats', [StatsController::class, 'getStats']);
    Route::get('/student/stats', [StatsController::class, 'getStudentStats']);

});