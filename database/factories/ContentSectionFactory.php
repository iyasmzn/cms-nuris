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
            'typography' => null,
            'layout' => 'media',
            'image' => 'content-sections/'.$this->faker->uuid().'.jpg',
            'image_position' => $this->faker->randomElement(array_keys(ContentSection::IMAGE_POSITIONS)),
            'items' => null,
            'items_columns' => 3,
            'items_ratio' => ContentSection::DEFAULT_CARD_RATIO,
            'carousel_autoplay' => true,
            'carousel_autoplay_delay' => 5,
            'carousel_pause_on_hover' => true,
            'carousel_loop' => true,
            'carousel_arrows' => true,
            'carousel_dots' => true,
            'background' => $this->faker->randomElement(['default', 'alt']),
            'background_pattern' => 'none',
            'background_pattern_opacity' => ContentSection::DEFAULT_PATTERN_OPACITY,
            'background_pattern_scale' => ContentSection::DEFAULT_PATTERN_SCALE,
            'background_pattern_color' => ContentSection::DEFAULT_PATTERN_COLOR,
            'background_pattern_custom_color' => null,
            'background_pattern_animated' => false,
            'background_pattern_motion' => ContentSection::DEFAULT_PATTERN_MOTION,
            'background_pattern_speed' => ContentSection::DEFAULT_PATTERN_SPEED,
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

    /**
     * Deretan kartu, lengkap dengan satu kartu bertombol dan satu kartu yang
     * seluruh badannya bisa diklik.
     *
     * @param  int  $count  jumlah kartu yang dibuat
     */
    public function withCards(int $count = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'layout' => 'cards',
            'items' => collect(range(1, $count))
                ->map(fn (int $index): array => [
                    'image' => 'content-sections/cards/kartu-'.$index.'.jpg',
                    'title' => 'Kartu '.$index,
                    'description' => $this->faker->sentence(8),
                    'cta_label' => $index === 1 ? 'Selengkapnya' : null,
                    'cta_url' => $index <= 2 ? '/kartu-'.$index : null,
                    'cta_new_tab' => false,
                ])
                ->all(),
        ]);
    }

    /**
     * Kartu yang sama, tapi berjalan dalam carousel.
     */
    public function withCarousel(int $count = 6): static
    {
        return $this->withCards($count)->state(fn (array $attributes): array => [
            'layout' => 'carousel',
        ]);
    }

    /**
     * Seksi tanpa deskripsi — judulnya saja sudah bercerita.
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    /**
     * Gaya huruf kustom pada tiap elemen teks.
     *
     * @param  array<string, array<string, mixed>>  $typography
     */
    public function withTypography(array $typography): static
    {
        return $this->state(fn (array $attributes) => [
            'typography' => $typography,
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
