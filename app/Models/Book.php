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

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'book_subject')
                    ->withTimestamps();
    }
    public function scopeRecommendedForStudent($query, User $student)
    {
        $yearLevel = $this->formatYearLevel($student->year_level);

        return $query->whereHas('subjects', function($query) use ($student, $yearLevel) {
                $query->where('year_level', $yearLevel)
                      ->where('department', $student->course);
            })
            ->with(['author', 'category', 'subjects'])
            ->select('books.*')
            ->selectRaw('
                (books.total_copies - (
                    SELECT COUNT(*) 
                    FROM book_loans 
                    WHERE book_loans.book_id = books.id 
                    AND book_loans.return_date IS NULL
                )) as available_copies
            ')
            ->havingRaw('available_copies > 0');
    }

    private function formatYearLevel($yearLevel)
    {
        // Remove any non-numeric characters and convert to integer
        $numericYear = (int) preg_replace('/[^0-9]/', '', $yearLevel);
        
        return $numericYear;
    }
}
