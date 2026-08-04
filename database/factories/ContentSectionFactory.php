<?php

namespace Database\Factories;

use App\Models\ContentSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentSection>
 */
class ContentSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'eyebrow' => $this->faker->words(2, true),
            'title' => rtrim($this->faker->sentence(5), '.'),
            'description' => '<p>'.$this->faker->paragraph(3).'</p>',
            'image' => 'content-sections/'.$this->faker->uuid().'.jpg',
            'image_position' => $this->faker->randomElement(array_keys(ContentSection::IMAGE_POSITIONS)),
            'background' => $this->faker->randomElement(array_keys(ContentSection::BACKGROUNDS)),
            'anchor' => null,
            'cta_label' => 'Selengkapnya',
            'cta_url' => $this->faker->url(),
            'cta_new_tab' => false,
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

    public function withoutCta(): static
    {
        return $this->state(fn (array $attributes) => [
            'cta_label' => null,
            'cta_url' => null,
        ]);
    }
}
