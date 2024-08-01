<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    
    protected $table = 'attendance';
    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
        'notes'
    ];
    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
