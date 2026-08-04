<?php

namespace Database\Factories;

use App\Models\AlumniStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlumniStat>
 */
class AlumniStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'icon' => $this->faker->randomElement(['🎓', '🏛️', '🌏', '💼', '📜', '🧑‍🎓']),
            'icon_image' => null,
            'label' => $this->faker->words(2, true),
            'value' => (string) $this->faker->numberBetween(10, 5000),
            'sub' => $this->faker->sentence(3),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
