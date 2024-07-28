<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $courses = ['BSIT', 'BEED', 'BSED', 'THEO'];
        $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        return [
            'role' => 'student',
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'course' => $this->faker->randomElement($courses),
            'year_level' => $this->faker->randomElement($yearLevels),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'profile_picture' => null,
            'address' => $this->faker->address,
            'birthday' => $this->faker->date('Y-m-d', '2000-05-15'),
            'contact_number' => $this->faker->phoneNumber,
            'position' => null,
            'department' => 'School of Computing',
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'), // or bcrypt('12345678')
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
