<?php

namespace App\Models;

use App\Services\EmbedVideo;
use Database\Factories\SlideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    /** @use HasFactory<SlideFactory> */
    use HasFactory;

    /**
     * Latar berupa gambar diam.
     */
    public const MEDIA_IMAGE = 'image';

    /**
     * Latar berupa file video yang diunggah, diputar mute & berulang.
     */
    public const MEDIA_VIDEO = 'video';

    /**
     * Latar berupa video YouTube, diputar mute & berulang.
     */
    public const MEDIA_YOUTUBE = 'youtube';

    /**
     * Teks tombol preview bila admin tidak mengisinya.
     */
    public const DEFAULT_VIDEO_BUTTON_LABEL = 'Tonton Video';

    protected $fillable = [
        'media_type', 'image', 'video_path', 'video_url',
        'video_preview_enabled', 'show_video_button', 'video_button_label', 'preview_video_url',
        'title', 'subtitle',
        'button_label', 'button_url',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'video_preview_enabled' => 'boolean',
            'show_video_button' => 'boolean',
        ];
    }

    /**
     * Tipe media latar, dengan label yang bisa dibaca manusia.
     *
     * @return array<string, string>
     */
    public static function mediaTypes(): array
    {
        return [
            self::MEDIA_IMAGE => 'Gambar',
            self::MEDIA_VIDEO => 'Video (Unggah File)',
            self::MEDIA_YOUTUBE => 'Video (YouTube)',
        ];
    }

    /**
     * Label untuk sebuah nilai tipe media.
     */
    public static function mediaTypeLabel(?string $type): string
    {
        return self::mediaTypes()[$type] ?? self::mediaTypes()[self::MEDIA_IMAGE];
    }

    /**
     * URL gambar — storage jika ada, fallback ke placeholder. Pada slide
     * bervideo, gambar ini dipakai sebagai poster/fallback.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/'.$this->image);
        }

        return 'https://picsum.photos/seed/hero-'.$this->id.'/1600/900';
    }

    /**
     * URL file video yang diunggah, atau null bila belum ada.
     */
    public function getVideoFileUrlAttribute(): ?string
    {
        return $this->video_path ? asset('storage/'.$this->video_path) : null;
    }

    /**
     * `src` iframe YouTube untuk latar: autoplay, mute (syarat semua browser
     * modern), berulang, tanpa kontrol & branding. Null bila bukan YouTube
     * atau URL-nya tidak bisa diurai.
     */
    public function getYoutubeBackgroundSrcAttribute(): ?string
    {
        if ($this->media_type !== self::MEDIA_YOUTUBE || blank($this->video_url)) {
            return null;
        }

        $id = EmbedVideo::extractId(EmbedVideo::PROVIDER_YOUTUBE, (string) $this->video_url);

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

    /**
     * Apakah slide ini menampilkan video sebagai latar (bukan gambar diam).
     */
    public function hasVideoBackground(): bool
    {
        return match ($this->media_type) {
            self::MEDIA_VIDEO => $this->video_file_url !== null,
            self::MEDIA_YOUTUBE => $this->youtube_background_src !== null,
            default => false,
        };
    }

    /**
     * Sumber video untuk modal preview: `preview_video_url` bila diisi, kalau
     * tidak jatuh ke media latar slide itu sendiri. `ratio` dipakai untuk
     * menyesuaikan tinggi pemutar (TikTok & Instagram tidak 16:9).
     *
     * @return array{type: 'embed'|'file', src: string, ratio: float}|null
     */
    public function previewSource(): ?array
    {
        if (filled($this->preview_video_url)) {
            $provider = EmbedVideo::detectProvider((string) $this->preview_video_url);
            $src = $provider ? EmbedVideo::embedSrc($provider, (string) $this->preview_video_url) : null;

            return $src ? [
                'type' => 'embed',
                'src' => $this->withEmbedAutoplay($src),
                'ratio' => round(EmbedVideo::aspectRatio($provider), 4),
            ] : null;
        }

        if ($this->media_type === self::MEDIA_YOUTUBE && filled($this->video_url)) {
            $src = EmbedVideo::embedSrc(EmbedVideo::PROVIDER_YOUTUBE, (string) $this->video_url);

            return $src ? [
                'type' => 'embed',
                'src' => $this->withEmbedAutoplay($src),
                'ratio' => round(EmbedVideo::aspectRatio(EmbedVideo::PROVIDER_YOUTUBE), 4),
            ] : null;
        }

        if ($this->media_type === self::MEDIA_VIDEO && $this->video_file_url !== null) {
            return [
                'type' => 'file',
                'src' => $this->video_file_url,
                'ratio' => round(EmbedVideo::aspectRatio(null), 4),
            ];
        }

        return null;
    }

    /**
     * Apakah slide ini bisa diputar penuh (bersuara) di modal.
     */
    public function hasVideoPreview(): bool
    {
        return $this->video_preview_enabled && $this->previewSource() !== null;
    }

    /**
     * Apakah tombol "tonton video" ditampilkan — hanya bila preview aktif.
     */
    public function showsVideoButton(): bool
    {
        return $this->show_video_button && $this->hasVideoPreview();
    }

    /**
     * Teks tombol preview, dengan fallback bawaan.
     */
    public function getVideoButtonTextAttribute(): string
    {
        return filled($this->video_button_label)
            ? (string) $this->video_button_label
            : self::DEFAULT_VIDEO_BUTTON_LABEL;
    }

    /**
     * @return Builder<static>
     */
    public static function active(): Builder
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Modal preview diputar setelah klik pengguna, jadi boleh autoplay
     * bersuara — kontrol player tetap tampil.
     */
    private function withEmbedAutoplay(string $src): string
    {
        $separator = str_contains($src, '?') ? '&' : '?';

        return $src.$separator.http_build_query([
            'autoplay' => 1,
            'rel' => 0,
            'playsinline' => 1,
        ]);
    }
}
