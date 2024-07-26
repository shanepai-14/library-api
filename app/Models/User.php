<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
       'id_number', 'role', 'first_name', 'middle_name', 'last_name',
        'course', 'year_level', 'gender', 'profile_picture', 'address',
        'birthday', 'contact_number', 'position', 'department', 'email',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id_number = self::generateUniqueId();
        });
    }

    private static function generateUniqueId()
    {
        $id_number = self::generateRandomNumberString(8); // Generate a random numeric string
    
        // Check if the id_number already exists
        while (self::where('id_number', $id_number)->exists()) {
            $id_number = self::generateRandomNumberString(8); // Generate again if exists
        }
    
        return $id_number;
    }
    private static function generateRandomNumberString($length)
{
    $digits = '0123456789';
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $digits[rand(0, strlen($digits) - 1)];
    }

    return $randomString;
}
}
