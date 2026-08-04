<?php

namespace App\Filament\Resources\ContentSections\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Models\ContentSection;
use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ContentSectionForm
{
    use InteractsWithImagePicker;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Konten')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->schema([
                    TextInput::make('eyebrow')
                        ->label('Label Kecil')
                        ->maxLength(60)
                        ->placeholder('Fasilitas Kami')
                        ->helperText('Teks kecil di atas judul. Kosongkan untuk menyembunyikan.')
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('Lingkungan Belajar yang Nyaman')
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Deskripsi')
                        ->required()
                        ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo'])
                        ->placeholder('Jelaskan seksi ini dalam beberapa kalimat...')
                        ->columnSpanFull(),
                ]),

            Section::make('Gambar & Tata Letak')
                ->icon(Heroicon::OutlinedPhoto)
                ->schema([
                    self::imagePicker(
                        key: 'image',
                        label: 'Gambar Seksi',
                        hint: 'Rasio 4:3. Akan di-resize ke 1000×750. Kosongkan bila seksi tampil tanpa gambar.',
                        accepted: ['image/jpeg', 'image/png', 'image/webp'],
                        width: 1000,
                        height: 750,
                        directory: 'content-sections',
                        aspectRatio: '4:3',
                    )->columnSpanFull(),

                    ToggleButtons::make('image_position')
                        ->label('Posisi Gambar')
                        ->options(ContentSection::IMAGE_POSITIONS)
                        ->icons([
                            'left' => Heroicon::OutlinedArrowLeftCircle,
                            'right' => Heroicon::OutlinedArrowRightCircle,
                        ])
                        ->default('right')
                        ->required()
                        ->inline()
                        ->helperText('Di layar ponsel gambar selalu tampil di atas teks.')
                        ->columnSpanFull(),
                ]),

            Section::make('Latar Belakang Seksi')
                ->description('Latar abu lembut, putih bersih, atau gambar penuh dengan efek blur, lapisan gelap, dan parallax.')
                ->icon(Heroicon::OutlinedSwatch)
                ->schema([
                    ToggleButtons::make('background')
                        ->label('Jenis Latar')
                        ->options(ContentSection::BACKGROUNDS)
                        ->icons([
                            'default' => Heroicon::OutlinedSquares2x2,
                            'alt' => Heroicon::OutlinedStop,
                            'image' => Heroicon::OutlinedPhoto,
                        ])
                        ->default('default')
                        ->required()
                        ->inline()
                        ->live()
                        ->helperText('Abu lembut = warna dasar halaman, sama seperti seksi bawaan. Selang-seling dengan putih agar antar seksi tidak menyatu.')
                        ->columnSpanFull(),

                    self::imagePicker(
                        key: 'background_image',
                        label: 'Gambar Latar',
                        hint: 'Gambar lebar (landscape). Akan di-resize ke 1920×1080.',
                        accepted: ['image/jpeg', 'image/png', 'image/webp'],
                        width: 1920,
                        height: 1080,
                        directory: 'content-sections/backgrounds',
                        aspectRatio: '16:9',
                    )
                        ->visible(self::usesBackgroundImage())
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->visible(self::usesBackgroundImage())
                        ->schema([
                            Select::make('background_blur')
                                ->label('Blur Latar')
                                ->options(ContentSection::BLUR_LEVELS)
                                ->default(0)
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->helperText('Mengaburkan gambar agar teks lebih mudah dibaca.'),

                            Select::make('background_overlay')
                                ->label('Lapisan Gelap')
                                ->options(ContentSection::OVERLAY_LEVELS)
                                ->default(0)
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->helperText('Menggelapkan gambar agar kontras teks meningkat.'),

                            Toggle::make('background_light_text')
                                ->label('Teks Warna Terang')
                                ->default(true)
                                ->onColor('success')
                                ->helperText('Nyalakan bila latarnya gelap, matikan bila latarnya terang.')
                                ->columnSpanFull(),

                            ToggleButtons::make('background_parallax_mode')
                                ->label('Gerak Saat Digulir')
                                ->options(ContentSection::PARALLAX_MODES)
                                ->icons([
                                    'none' => Heroicon::OutlinedBars2,
                                    'scroll' => Heroicon::OutlinedArrowsUpDown,
                                    'fixed' => Heroicon::OutlinedLockClosed,
                                ])
                                ->default('none')
                                ->required()
                                ->inline()
                                ->live()
                                ->helperText('Bergeser: gambar tertinggal di belakang konten. Diam: gambar terkunci ke layar dan konten meluncur di atasnya.')
                                ->columnSpanFull(),

                            Slider::make('background_parallax_speed')
                                ->label('Kekuatan Parallax')
                                ->range(minValue: 1, maxValue: 100)
                                ->step(1)
                                ->tooltips()
                                ->default(30)
                                ->required()
                                ->visible(fn (Get $get): bool => $get('background_parallax_mode') === 'scroll')
                                ->helperText('1 = nyaris diam, 100 = geser paling jauh. Makin tinggi, gambar latar makin diperbesar agar tepinya tidak menyingkap — pakai gambar resolusi tinggi untuk nilai besar.')
                                ->columnSpanFull(),
                        ]),
                ]),

            Section::make('Tombol CTA')
                ->description('Opsional. Tombol muncul hanya bila teks dan tautannya terisi.')
                ->icon(Heroicon::OutlinedCursorArrowRays)
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('cta_label')
                            ->label('Teks Tombol')
                            ->maxLength(60)
                            ->placeholder('Selengkapnya')
                            ->requiredWith('cta_url'),

                        TextInput::make('cta_url')
                            ->label('Tautan Tombol')
                            ->maxLength(255)
                            ->placeholder('https://... atau /profil')
                            ->helperText('Boleh URL lengkap atau path internal seperti /profil.')
                            ->requiredWith('cta_label')
                            ->rule('regex:/^(https?:\/\/|\/|#|mailto:|tel:)/')
                            ->validationMessages([
                                'regex' => 'Tautan harus diawali http://, https://, /, #, mailto:, atau tel:.',
                            ]),

                        Toggle::make('cta_new_tab')
                            ->label('Buka di Tab Baru')
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
                ]),

            Section::make('Pengaturan Tampil')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('anchor')
                            ->label('ID Anchor')
                            ->maxLength(60)
                            ->placeholder('fasilitas')
                            ->helperText('Untuk tautan menu, misal #fasilitas. Kosongkan bila tidak perlu.'),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->hint('Angka kecil tampil lebih dulu.'),

                        Toggle::make('is_published')
                            ->label('Tampilkan di Website')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ]),
                ]),
        ]);
    }

    /**
     * Opsi latar gambar hanya relevan saat jenis latarnya "Gambar".
     */
    private static function usesBackgroundImage(): Closure
    {
        return fn (Get $get): bool => $get('background') === 'image';
    }
}
