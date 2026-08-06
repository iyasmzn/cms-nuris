<?php

namespace App\Filament\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithVideoPicker;
use App\Models\Slide;
use App\Services\EmbedVideo;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Cover hero halaman & program. Pilihannya disamakan dengan slide hero halaman
 * depan — gambar, berkas video, atau video YouTube, plus pop-up preview — hanya
 * saja nilainya disimpan di kolom JSON `hero` milik recordnya.
 */
class PageHeroFields
{
    use InteractsWithImagePicker;
    use InteractsWithVideoPicker;

    /**
     * Kunci gambar & video di dalam array `hero`, dipakai halaman Create/Edit
     * untuk menyelesaikan pilihan media picker sebelum disimpan.
     */
    public const IMAGE_KEYS = ['image'];

    public const VIDEO_KEYS = ['video_path'];

    public const EMBED_KEYS = ['video_url', 'preview_video_url'];

    /**
     * @param  string  $directory  folder penyimpanan media hero
     * @param  string  $imageHint  penjelasan gambar, berbeda tiap tipe konten
     */
    public static function make(string $directory, string $imageHint): Section
    {
        return Section::make('Cover Hero')
            ->description('Latar bagian atas halaman: gambar diam, berkas video, atau video YouTube. Video latar selalu diputar tanpa suara & berulang — begitulah syarat autoplay di semua browser.')
            ->icon(Heroicon::OutlinedFilm)
            ->collapsible()
            ->collapsed()
            ->schema([
                ToggleButtons::make('hero.media_type')
                    ->label('Tipe Cover')
                    ->options(Slide::mediaTypes())
                    ->icons([
                        Slide::MEDIA_IMAGE => Heroicon::OutlinedPhoto,
                        Slide::MEDIA_VIDEO => Heroicon::OutlinedFilm,
                        Slide::MEDIA_YOUTUBE => Heroicon::OutlinedVideoCamera,
                    ])
                    ->default(Slide::MEDIA_IMAGE)
                    ->inline()
                    ->live()
                    ->columnSpanFull(),

                self::videoPicker(
                    key: 'hero.video_path',
                    label: 'Berkas Video',
                    hint: 'MP4/WebM, maksimal 20MB. Video pendek (10–20 detik, 1280×720) menjaga halaman tetap ringan.',
                    directory: $directory,
                )
                    ->visible(self::usesMedia(Slide::MEDIA_VIDEO))
                    ->columnSpanFull(),

                TextInput::make('hero.video_url')
                    ->label('URL Video YouTube')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->helperText('Video akan diputar tanpa suara, berulang, tanpa kontrol. Videonya juga ditambahkan ke Media.')
                    ->required(self::usesMedia(Slide::MEDIA_YOUTUBE))
                    ->visible(self::usesMedia(Slide::MEDIA_YOUTUBE))
                    ->rule(static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                        if (blank($value)) {
                            return;
                        }

                        if (EmbedVideo::detectProvider((string) $value) !== EmbedVideo::PROVIDER_YOUTUBE) {
                            $fail('URL latar harus dari YouTube.');

                            return;
                        }

                        if (! EmbedVideo::isValid((string) $value)) {
                            $fail('Tidak dapat membaca ID video dari URL. Pastikan ini link video, bukan link channel.');
                        }
                    })
                    ->columnSpanFull(),

                self::imagePicker(
                    key: 'hero.image',
                    label: 'Gambar Cover',
                    hint: $imageHint,
                    accepted: ['image/jpeg', 'image/png', 'image/webp'],
                    width: 1600,
                    height: 900,
                    directory: $directory,
                    aspectRatio: '16:9',
                    withMeta: false,
                )->columnSpanFull(),

                Toggle::make('hero.video_preview_enabled')
                    ->label('Aktifkan Preview Video')
                    ->helperText('Video bisa dibuka di pop-up (bersuara, dengan kontrol) saat diklik pengunjung.')
                    ->live()
                    ->columnSpanFull(),

                TextInput::make('hero.preview_video_url')
                    ->label('URL Video Preview')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->helperText('Kosongkan untuk memutar video cover ini. Isi bila video pop-up berbeda dengan latarnya (YouTube, TikTok, atau Instagram).')
                    ->visible(fn (Get $get): bool => (bool) $get('hero.video_preview_enabled'))
                    ->rule(static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                        if (blank($value)) {
                            return;
                        }

                        if (! EmbedVideo::isValid((string) $value)) {
                            $fail('URL harus link video dari YouTube, TikTok, atau Instagram.');
                        }
                    })
                    ->columnSpanFull(),

                Grid::make(2)
                    ->visible(fn (Get $get): bool => (bool) $get('hero.video_preview_enabled'))
                    ->schema([
                        Toggle::make('hero.show_video_button')
                            ->label('Tampilkan Tombol Preview')
                            ->helperText('Nonaktif: pop-up tetap bisa dibuka dengan mengklik area video.')
                            ->live(),

                        TextInput::make('hero.video_button_label')
                            ->label('Teks Tombol Preview')
                            ->maxLength(100)
                            ->placeholder(Slide::DEFAULT_VIDEO_BUTTON_LABEL)
                            ->helperText('Kosongkan untuk memakai "'.Slide::DEFAULT_VIDEO_BUTTON_LABEL.'".')
                            ->visible(fn (Get $get): bool => (bool) $get('hero.show_video_button')),
                    ]),
            ]);
    }

    private static function usesMedia(string $type): Closure
    {
        return fn (Get $get): bool => $get('hero.media_type') === $type;
    }
}
