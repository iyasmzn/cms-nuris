<?php

namespace Database\Factories;

use App\Models\FloatingButton;
use App\Support\PageTargets;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FloatingButton>
 */
class FloatingButtonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->unique()->words(3, true),
            'url' => 'https://wa.me/62812'.fake()->numerify('#######'),
            'icon' => '💬',
            'color' => fake()->hexColor(),
            'open_in_new_tab' => true,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
            'display_mode' => FloatingButton::DISPLAY_ALL,
            'display_targets' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * @param  list<string>  $targets  Page target keys, see {@see PageTargets}.
     */
    public function onlyOn(array $targets): static
    {
        return $this->state([
            'display_mode' => FloatingButton::DISPLAY_ONLY,
            'display_targets' => $targets,
        ]);
    }

    /**
     * @param  list<string>  $targets  Page target keys, see {@see PageTargets}.
     */
    public function exceptOn(array $targets): static
    {
        return $this->state([
            'display_mode' => FloatingButton::DISPLAY_EXCEPT,
            'display_targets' => $targets,
        ]);
    }
}
