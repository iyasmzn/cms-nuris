<?php

namespace Database\Factories;

use App\Models\AlumniUniversity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlumniUniversity>
 */
class AlumniUniversityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Universitas '.$this->faker->unique()->city(),
            'logo' => null,
            'url' => $this->faker->optional()->url(),
            'sort_order' => $this->faker->numberBetween(0, 20),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }
}
