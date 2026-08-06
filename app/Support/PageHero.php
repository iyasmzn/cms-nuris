<?php

namespace App\Support;

use App\Models\Slide;
use App\Services\EmbedVideo;

/**
 * Cover hero satu halaman atau program. Pilihan medianya sama dengan slide hero
 * halaman depan — gambar, berkas video, atau video YouTube, lengkap dengan
 * pop-up preview — hanya saja nilainya tersimpan sebagai satu kolom JSON milik
 * recordnya, bukan tabel slide tersendiri.
 */
class PageHero
{
    public function __construct(
        public readonly string $mediaType = Slide::MEDIA_IMAGE,
        public readonly ?string $image = null,
        public readonly ?string $videoPath = null,
        public readonly ?string $videoUrl = null,
        public readonly bool $videoPreviewEnabled = false,
        public readonly ?string $previewVideoUrl = null,
        public readonly bool $showVideoButton = false,
        public readonly ?string $videoButtonLabel = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $hero
     */
    public static function fromArray(?array $hero): self
    {
        $hero ??= [];

        $type = (string) ($hero['media_type'] ?? Slide::MEDIA_IMAGE);

        return new self(
            mediaType: array_key_exists($type, Slide::mediaTypes()) ? $type : Slide::MEDIA_IMAGE,
            image: $hero['image'] ?? null,
            videoPath: $hero['video_path'] ?? null,
            videoUrl: $hero['video_url'] ?? null,
            videoPreviewEnabled: (bool) ($hero['video_preview_enabled'] ?? false),
            previewVideoUrl: $hero['preview_video_url'] ?? null,
            showVideoButton: (bool) ($hero['show_video_button'] ?? false),
            videoButtonLabel: $hero['video_button_label'] ?? null,
        );
    }

    /**
     * Gambar hero, atau null bila halaman memakai gambar bawaannya sendiri.
     */
    public function imageUrl(): ?string
    {
        return icon_url($this->image);
    }

    public function videoFileUrl(): ?string
    {
        return filled($this->videoPath) ? asset('storage/'.$this->videoPath) : null;
    }

    /**
     * `src` iframe YouTube untuk latar: autoplay, mute, berulang, tanpa kontrol.
     */
    public function youtubeBackgroundSrc(): ?string
    {
        if ($this->mediaType !== Slide::MEDIA_YOUTUBE || blank($this->videoUrl)) {
            return null;
        }

        $id = EmbedVideo::extractId(EmbedVideo::PROVIDER_YOUTUBE, (string) $this->videoUrl);

        if ($id === null) {
            return null;
        }

        return "https://www.youtube-nocookie.com/embed/{$id}?".http_build_query([
            'autoplay' => 1,
            'mute' => 1,
            'loop' => 1,
            'playlist' => $id,
            'controls' => 0,
            'playsinline' => 1,
            'rel' => 0,
            'modestbranding' => 1,
            'disablekb' => 1,
            'iv_load_policy' => 3,
        ]);
    }

    public function hasVideoBackground(): bool
    {
        return match ($this->mediaType) {
            Slide::MEDIA_VIDEO => $this->videoFileUrl() !== null,
            Slide::MEDIA_YOUTUBE => $this->youtubeBackgroundSrc() !== null,
            default => false,
        };
    }

    /**
     * Hero ini benar-benar punya media sendiri untuk dipasang sebagai latar.
     */
    public function hasMedia(): bool
    {
        return filled($this->image) || $this->hasVideoBackground();
    }

    /**
     * Sumber video untuk pop-up preview, bentuknya sama dengan milik slide.
     *
     * @return array{type: 'embed'|'file', src: string, ratio: float}|null
     */
    public function previewSource(): ?array
    {
        if (filled($this->previewVideoUrl)) {
            $provider = EmbedVideo::detectProvider((string) $this->previewVideoUrl);
            $src = $provider ? EmbedVideo::embedSrc($provider, (string) $this->previewVideoUrl) : null;

            return $src ? [
                'type' => 'embed',
                'src' => $this->withAutoplay($src),
                'ratio' => round(EmbedVideo::aspectRatio($provider), 4),
            ] : null;
        }

        if ($this->mediaType === Slide::MEDIA_YOUTUBE && filled($this->videoUrl)) {
            $src = EmbedVideo::embedSrc(EmbedVideo::PROVIDER_YOUTUBE, (string) $this->videoUrl);

            return $src ? [
                'type' => 'embed',
                'src' => $this->withAutoplay($src),
                'ratio' => round(EmbedVideo::aspectRatio(EmbedVideo::PROVIDER_YOUTUBE), 4),
            ] : null;
        }

        if ($this->mediaType === Slide::MEDIA_VIDEO && $this->videoFileUrl() !== null) {
            return [
                'type' => 'file',
                'src' => $this->videoFileUrl(),
                'ratio' => round(EmbedVideo::aspectRatio(null), 4),
            ];
        }

        return null;
    }

    public function hasVideoPreview(): bool
    {
        return $this->videoPreviewEnabled && $this->previewSource() !== null;
    }

    public function showsVideoButton(): bool
    {
        return $this->showVideoButton && $this->hasVideoPreview();
    }

    public function videoButtonText(): string
    {
        return filled($this->videoButtonLabel)
            ? (string) $this->videoButtonLabel
            : Slide::DEFAULT_VIDEO_BUTTON_LABEL;
    }

    private function withAutoplay(string $src): string
    {
        $separator = str_contains($src, '?') ? '&' : '?';

        return $src.$separator.http_build_query([
            'autoplay' => 1,
            'rel' => 0,
            'playsinline' => 1,
        ]);
    }
}
