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
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'full_name'      => fake()->name(),
            'email'          => fake()->unique()->safeEmail(),
            'password'       => static::$password ??= Hash::make('User@123'),
            'phone'          => fake()->numerify('09########'),
            'role'           => 'user',
            'status'         => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static  { return $this->state(fn () => ['role' => 'admin']); }
    public function locked(): static { return $this->state(fn () => ['status' => 'locked']); }
}
