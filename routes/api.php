<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\BookLoanController;
use App\Http\Controllers\Api\UserController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login'])->name('login');

Route::group(['middleware' => ['auth:sanctum']] ,function () {

    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/users', [UserController::class, 'index']);
    Route::apiResource('user', UserController::class);
    Route::apiResource('books', BookController::class);
    Route::apiResource('authors', AuthorController::class);
    Route::apiResource('attendances', AttendanceController::class); 
    Route::apiResource('book-loans', BookLoanController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('/books/category/{categoryId}', [BookController::class, 'getBooksByCategory']);
    Route::get('/books/author/{authorId}', [BookController::class, 'getBooksByAuthor']);
    Route::get('/author/all', [AuthorController::class, 'allAuthor']);
    Route::get('/category/all', [CategoryController::class, 'allCategory']);
    Route::get('/books/{book_id}/active-loans', [BookLoanController::class, 'fetchActiveLoans']);
    Route::get('/book-loans/{id}/return', [BookLoanController::class, 'checkEligibleForReturn']);
    Route::post('/book-loans/{bookLoanId}/return', [BookLoanController::class, 'returnBook']);

});