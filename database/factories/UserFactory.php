<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\Jass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => Rol::Presidente,
            'jass_id' => Jass::factory(),
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

    public function superadmin(): static
    {
        return $this->state(fn () => [
            'rol' => Rol::Superadmin,
            'jass_id' => null,
        ]);
    }

    public function presidente(?Jass $jass = null): static
    {
        return $this->state(fn () => [
            'rol' => Rol::Presidente,
            'jass_id' => $jass?->id ?? Jass::factory(),
        ]);
    }

    public function tesorero(?Jass $jass = null): static
    {
        return $this->state(fn () => [
            'rol' => Rol::Tesorero,
            'jass_id' => $jass?->id ?? Jass::factory(),
        ]);
    }

    public function fiscal(?Jass $jass = null): static
    {
        return $this->state(fn () => [
            'rol' => Rol::Fiscal,
            'jass_id' => $jass?->id ?? Jass::factory(),
        ]);
    }

    public function vocal(?Jass $jass = null): static
    {
        return $this->state(fn () => [
            'rol' => Rol::Vocal,
            'jass_id' => $jass?->id ?? Jass::factory(),
        ]);
    }
}
