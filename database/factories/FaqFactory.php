<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /** @var array<string> */
    private static array $categories = ['SPMB', 'Akademik', 'Biaya', 'Fasilitas'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => rtrim($this->faker->sentence(8), '.').'?',
            'answer' => '<p>'.$this->faker->paragraph(3).'</p>',
            'category' => $this->faker->randomElement(self::$categories),
            'is_published' => $this->faker->boolean(85),
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }
}
