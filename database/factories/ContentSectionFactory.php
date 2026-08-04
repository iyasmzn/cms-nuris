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
            'background' => $this->faker->randomElement(['default', 'alt']),
            'background_image' => null,
            'background_blur' => 0,
            'background_overlay' => 0,
            'background_parallax_mode' => 'none',
            'background_parallax_speed' => 30,
            'background_light_text' => true,
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

    /**
     * Latar gambar penuh dengan blur, lapisan gelap, dan parallax menyala.
     */
    public function withBackgroundImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'background' => 'image',
            'background_image' => 'content-sections/backgrounds/'.$this->faker->uuid().'.jpg',
            'background_blur' => 8,
            'background_overlay' => 45,
            'background_parallax_mode' => 'scroll',
            'background_parallax_speed' => 30,
            'background_light_text' => true,
        ]);
    }

    /**
     * Latar gambar yang terkunci ke layar saat halaman digulir.
     */
    public function withFixedBackground(): static
    {
        return $this->withBackgroundImage()->state(fn (array $attributes) => [
            'background_parallax_mode' => 'fixed',
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
