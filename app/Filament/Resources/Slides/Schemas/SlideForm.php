<?php

namespace App\Filament\Resources\Slides\Schemas;

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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SlideForm
{
    use InteractsWithImagePicker;
    use InteractsWithVideoPicker;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Media Latar')
                ->description('Latar slide bisa berupa gambar diam atau video. Video latar selalu diputar tanpa suara & berulang — begitulah syarat autoplay di semua browser.')
                ->schema([
                    ToggleButtons::make('media_type')
                        ->label('Tipe Latar')
                        ->options(Slide::mediaTypes())
                        ->icons([
                            Slide::MEDIA_IMAGE => Heroicon::OutlinedPhoto,
                            Slide::MEDIA_VIDEO => Heroicon::OutlinedFilm,
                            Slide::MEDIA_YOUTUBE => Heroicon::OutlinedVideoCamera,
                        ])
                        ->default(Slide::MEDIA_IMAGE)
                        ->required()
                        ->inline()
                        ->live()
                        ->columnSpanFull(),

                    self::videoPicker(
                        key: 'video_path',
                        label: 'Berkas Video',
                        hint: 'MP4/WebM, maksimal 20MB. Video pendek (10–20 detik, 1280×720) menjaga halaman tetap ringan.',
                        directory: 'slides',
                    )
                        ->visible(fn (Get $get): bool => $get('media_type') === Slide::MEDIA_VIDEO)
                        ->columnSpanFull(),

                    TextInput::make('video_url')
                        ->label('URL Video YouTube')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://www.youtube.com/watch?v=...')
                        ->helperText('Video akan diputar tanpa suara, berulang, tanpa kontrol. Videonya juga ditambahkan ke Media.')
                        ->required(fn (Get $get): bool => $get('media_type') === Slide::MEDIA_YOUTUBE)
                        ->visible(fn (Get $get): bool => $get('media_type') === Slide::MEDIA_YOUTUBE)
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
                        key: 'image',
                        label: 'Gambar Slide',
                        hint: 'Akan di-resize ke 1600×900px (16:9). Biarkan kosong untuk menggunakan placeholder.',
                        accepted: ['image/jpeg', 'image/png', 'image/webp'],
                        width: 1600,
                        height: 900,
                        directory: 'slides',
                        aspectRatio: '16:9',
                    ),
                ]),

            Section::make('Preview Video')
                ->description('Opsional: tombol untuk memutar video versi penuh (bersuara) di jendela pop-up.')
                ->schema([
                    Toggle::make('video_preview_enabled')
                        ->label('Aktifkan Preview Video')
                        ->helperText('Video bisa dibuka di pop-up saat diklik pengunjung.')
                        ->live()
                        ->columnSpanFull(),

                    TextInput::make('preview_video_url')
                        ->label('URL Video Preview')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://www.youtube.com/watch?v=...')
                        ->helperText('Kosongkan untuk memutar video latar slide ini. Isi bila video pop-up berbeda dengan latarnya (YouTube, TikTok, atau Instagram).')
                        ->visible(fn (Get $get): bool => (bool) $get('video_preview_enabled'))
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
                        ->schema([
                            Toggle::make('show_video_button')
                                ->label('Tampilkan Tombol Preview')
                                ->helperText('Nonaktif: pop-up tetap bisa dibuka dengan mengklik area video.')
                                ->live(),

                            TextInput::make('video_button_label')
                                ->label('Teks Tombol Preview')
                                ->maxLength(100)
                                ->placeholder(Slide::DEFAULT_VIDEO_BUTTON_LABEL)
                                ->helperText('Kosongkan untuk memakai "'.Slide::DEFAULT_VIDEO_BUTTON_LABEL.'".')
                                ->visible(fn (Get $get): bool => (bool) $get('show_video_button')),
                        ])
                        ->visible(fn (Get $get): bool => (bool) $get('video_preview_enabled')),
                ]),

            Section::make('Konten')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('Unggul dalam Akademik')
                        ->columnSpanFull(),

                    TextInput::make('subtitle')
                        ->label('Subjudul / Deskripsi')
                        ->maxLength(500)
                        ->placeholder('Raih prestasi terbaik bersama guru-guru berpengalaman.')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('button_label')
                            ->label('Teks Tombol CTA')
                            ->maxLength(100)
                            ->placeholder('Daftar Sekarang')
                            ->hint('Kosongkan jika tidak perlu tombol.'),

                        TextInput::make('button_url')
                            ->label('URL Tombol CTA')
                            ->maxLength(255)
                            ->placeholder('https://... atau #spmb'),
                    ]),
                ]),

            Section::make('Pengaturan')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->hint('Angka kecil tampil lebih dulu.'),

                        Toggle::make('is_active')
                            ->label('Aktif / Tampilkan')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ]),
                ]),
        ]);
    }
}
