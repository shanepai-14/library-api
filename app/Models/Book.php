<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;
    protected $fillable = [
        'title', 'author_id', 'category_id', 'isbn', 'description', 'total_copies',
        'publication_year', 'publisher', 'language', 'book_price', 'image'
    ];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function bookLoans()
{
    return $this->hasMany(BookLoan::class);
}
}
