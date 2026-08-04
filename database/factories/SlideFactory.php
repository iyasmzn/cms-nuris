<?php

namespace Database\Factories;

use App\Models\Slide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slide>
 */
class SlideFactory extends Factory
{
    /**
     * Define the model's default state (a still-image slide).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'media_type' => Slide::MEDIA_IMAGE,
            'image' => 'slides/'.fake()->uuid().'.jpg',
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(8),
            'button_label' => null,
            'button_url' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * A slide whose background is an uploaded video file.
     */
    public function videoFile(string $path = 'slides/hero.mp4'): static
    {
        return $this->state(fn (): array => [
            'media_type' => Slide::MEDIA_VIDEO,
            'video_path' => $path,
        ]);
    }

    /**
     * A slide whose background is a YouTube video.
     */
    public function youtube(string $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'): static
    {
        return $this->state(fn (): array => [
            'media_type' => Slide::MEDIA_YOUTUBE,
            'video_url' => $url,
        ]);
    }

    /**
     * Video playable in a pop-up, optionally behind a labelled button.
     */
    public function withVideoPreview(bool $withButton = true, ?string $label = null, ?string $url = null): static
    {
        return $this->state(fn (): array => [
            'video_preview_enabled' => true,
            'show_video_button' => $withButton,
            'video_button_label' => $label,
            'preview_video_url' => $url,
        ]);
    }

    /**
     * Hidden from the public hero slider.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
