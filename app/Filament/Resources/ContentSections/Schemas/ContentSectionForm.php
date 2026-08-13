<?php

namespace App\Filament\Resources\ContentSections\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Models\ContentSection;
use App\Support\SectionPatterns;
use App\Support\SectionTypography;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Fieldset;
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
                        ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo'])
                        ->placeholder('Jelaskan seksi ini dalam beberapa kalimat...')
                        ->helperText('Opsional. Kosongkan bila judul saja sudah cukup.')
                        // Editor menyisakan <p></p> saat isinya dihapus; simpan sebagai kosong
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled(trim(strip_tags((string) $state)))
                            ? $state
                            : null)
                        ->columnSpanFull(),
                ]),

            Section::make('Tipografi')
                ->description('Perataan, ketebalan, ukuran, dan warna huruf tiap elemen teks. Biarkan kosong untuk memakai gaya bawaan rancangan seksi.')
                ->icon(Heroicon::OutlinedLanguage)
                ->collapsible()
                ->collapsed()
                ->schema([
                    ...collect(SectionTypography::ELEMENTS)
                        ->map(fn (string $label, string $element): Fieldset => self::typographyFieldset($element, $label))
                        ->values()
                        ->all(),
                ]),

            Section::make('Bentuk Isi Seksi')
                ->description('Gambar berdampingan teks, deretan kartu, atau kartu yang berjalan otomatis.')
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->schema([
                    ToggleButtons::make('layout')
                        ->label('Tata Letak')
                        ->options(ContentSection::LAYOUTS)
                        ->icons([
                            'media' => Heroicon::OutlinedPhoto,
                            'cards' => Heroicon::OutlinedSquares2x2,
                            'carousel' => Heroicon::OutlinedArrowsRightLeft,
                        ])
                        ->default('media')
                        ->required()
                        ->inline()
                        ->live()
                        ->helperText('Judul dan deskripsi tetap tampil di semua tata letak — pada kartu, keduanya berdiri di atas kartunya.')
                        ->columnSpanFull(),
                ]),

            Section::make('Gambar & Tata Letak')
                ->icon(Heroicon::OutlinedPhoto)
                ->visible(self::usesLayout('media'))
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

            Section::make('Kartu')
                ->description('Setiap kartu punya gambar, judul, deskripsi singkat, dan tombol CTA sendiri. Seret untuk mengubah urutannya.')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->visible(self::usesCards())
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->addActionLabel('Tambah Kartu')
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): string => $state['title'] ?? 'Kartu baru')
                        ->defaultItems(0)
                        ->schema([
                            self::imagePicker(
                                key: 'image',
                                label: 'Gambar Kartu',
                                hint: 'Rasio 4:3. Akan di-resize ke 800×600. Kosongkan bila kartu tampil tanpa gambar.',
                                accepted: ['image/jpeg', 'image/png', 'image/webp'],
                                width: 800,
                                height: 600,
                                directory: 'content-sections/cards',
                                aspectRatio: '4:3',
                                withMeta: false,
                            )->columnSpanFull(),

                            TextInput::make('title')
                                ->label('Judul Kartu')
                                ->required()
                                ->maxLength(120)
                                ->placeholder('Tahfidz Al-Qur\'an')
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label('Deskripsi Singkat')
                                ->rows(3)
                                ->maxLength(300)
                                ->placeholder('Satu sampai dua kalimat tentang kartu ini.')
                                ->columnSpanFull(),

                            Grid::make(2)->schema([
                                TextInput::make('cta_label')
                                    ->label('Teks Tombol')
                                    ->maxLength(60)
                                    ->placeholder('Selengkapnya')
                                    ->helperText('Kosongkan bila cukup seluruh kartunya yang bisa diklik.'),

                                TextInput::make('cta_url')
                                    ->label('Tautan Kartu')
                                    ->maxLength(255)
                                    ->placeholder('https://... atau /program')
                                    ->helperText('Boleh path internal seperti /program atau #profil, boleh juga URL situs lain.')
                                    ->requiredWith('cta_label')
                                    ->rule('regex:/^(https?:\/\/|\/|#|mailto:|tel:)/')
                                    ->validationMessages([
                                        'regex' => 'Tautan harus diawali http://, https://, /, #, mailto:, atau tel:.',
                                    ]),

                                Toggle::make('cta_new_tab')
                                    ->label('Buka di Tab Baru')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => filled($get('cta_url')))
                                    ->columnSpanFull(),
                            ]),
                        ])
                        ->columnSpanFull(),

                    Select::make('items_columns')
                        ->label('Kartu Sebaris')
                        ->options(ContentSection::ITEM_COLUMNS)
                        ->default(3)
                        ->required()
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->helperText('Berlaku di layar lebar. Layar ponsel selalu satu kartu, layar sedang paling banyak dua.')
                        ->columnSpanFull(),

                    Select::make('items_ratio')
                        ->label('Ratio Gambar Kartu')
                        ->options(ContentSection::CARD_RATIOS)
                        ->default(ContentSection::DEFAULT_CARD_RATIO)
                        ->required()
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->helperText('Bentuk area gambar di setiap kartu. Gambar dipotong menyesuaikan bidangnya, jadi tinggi gambar seragam meski ukuran aslinya berbeda-beda. Kartu tanpa gambar tidak terpengaruh.')
                        ->columnSpanFull(),
                ]),

            Section::make('Pengaturan Carousel')
                ->description('Perilaku kartu yang berjalan: jalan sendiri, jeda perpindahan, dan alat navigasinya.')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->visible(self::usesLayout('carousel'))
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('carousel_autoplay')
                            ->label('Jalan Sendiri (Autoplay)')
                            ->default(true)
                            ->onColor('success')
                            ->live()
                            ->helperText('Kartu berpindah sendiri sesuai jeda di bawah.'),

                        Toggle::make('carousel_loop')
                            ->label('Kembali ke Awal')
                            ->default(true)
                            ->onColor('success')
                            ->helperText('Setelah kartu terakhir, kembali ke kartu pertama.'),

                        Slider::make('carousel_autoplay_delay')
                            ->label('Jeda Perpindahan (detik)')
                            ->range(minValue: ContentSection::AUTOPLAY_MIN_DELAY, maxValue: ContentSection::AUTOPLAY_MAX_DELAY)
                            ->step(1)
                            ->tooltips()
                            ->default(5)
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('carousel_autoplay'))
                            ->columnSpanFull(),

                        Toggle::make('carousel_pause_on_hover')
                            ->label('Berhenti saat Disorot Kursor')
                            ->default(true)
                            ->onColor('success')
                            ->visible(fn (Get $get): bool => (bool) $get('carousel_autoplay'))
                            ->helperText('Matikan agar kartu terus berjalan meski kursor berada di atasnya. Perpindahan tetap berhenti saat kartunya disentuh di ponsel atau dijelajahi lewat keyboard.')
                            ->columnSpanFull(),

                        Toggle::make('carousel_arrows')
                            ->label('Tombol Panah')
                            ->default(true)
                            ->onColor('success')
                            ->helperText('Tampil di layar lebar; di ponsel kartunya cukup diusap.'),

                        Toggle::make('carousel_dots')
                            ->label('Titik Navigasi')
                            ->default(true)
                            ->onColor('success')
                            ->helperText('Penanda halaman di bawah kartu.'),
                    ]),
                ]),

            Section::make('Latar Belakang Seksi')
                ->description('Latar abu lembut, putih bersih, atau gambar penuh dengan efek blur, lapisan gelap, dan parallax. Latar polos bisa dihias pola SVG.')
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

                    Grid::make(2)
                        ->visible(fn (Get $get): bool => $get('background') !== 'image')
                        ->schema([
                            Select::make('background_pattern')
                                ->label('Pola Latar')
                                ->options(SectionPatterns::selectOptions())
                                ->allowHtml()
                                ->default('none')
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->live()
                                ->helperText('Hiasan SVG halus di atas warna dasar, diwarnai mengikuti warna utama tema.'),

                            Select::make('background_pattern_opacity')
                                ->label('Kepekatan Pola')
                                ->options(ContentSection::PATTERN_OPACITY_LEVELS)
                                ->default(ContentSection::DEFAULT_PATTERN_OPACITY)
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->visible(fn (Get $get): bool => $get('background_pattern') !== 'none')
                                ->helperText('Makin pekat makin terlihat. Pola yang terlalu kuat membuat teks susah dibaca.'),

                            Select::make('background_pattern_scale')
                                ->label('Ukuran Pola')
                                ->options(ContentSection::PATTERN_SCALES)
                                ->default(ContentSection::DEFAULT_PATTERN_SCALE)
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->visible(fn (Get $get): bool => $get('background_pattern') !== 'none')
                                ->helperText('Ukuran ubin polanya. Makin rapat, makin ramai; seksi lebar biasanya lebih enak dengan pola besar.'),

                            Select::make('background_pattern_color')
                                ->label('Warna Pola')
                                ->options(ContentSection::PATTERN_COLORS)
                                ->default(ContentSection::DEFAULT_PATTERN_COLOR)
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->live()
                                ->visible(fn (Get $get): bool => $get('background_pattern') !== 'none')
                                ->helperText('Pilihan bertoken ikut berubah sendiri saat warna tema diganti. Putih berguna di atas latar seksi yang gelap.'),

                            ColorPicker::make('background_pattern_custom_color')
                                ->label('Hex Warna Pola')
                                ->default('#08484A')
                                ->visible(fn (Get $get): bool => $get('background_pattern') !== 'none'
                                    && $get('background_pattern_color') === 'custom')
                                ->helperText('Kosong atau tidak berbentuk hex akan kembali ke warna utama tema.'),

                            Toggle::make('background_pattern_animated')
                                ->label('Animasikan Pola')
                                ->default(false)
                                ->onColor('success')
                                ->live()
                                ->visible(fn (Get $get): bool => $get('background_pattern') !== 'none')
                                ->helperText('Pola bergerak pelan. Otomatis padam bagi pengunjung yang menyetel perangkatnya untuk mengurangi gerak. Pakai seperlunya — beberapa seksi bergerak sekaligus membuat halaman terasa gelisah.')
                                ->columnSpanFull(),

                            Select::make('background_pattern_motion')
                                ->label('Jenis Gerak')
                                ->options(ContentSection::PATTERN_MOTIONS)
                                ->default(ContentSection::DEFAULT_PATTERN_MOTION)
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->live()
                                ->visible(fn (Get $get): bool => $get('background_pattern') !== 'none' && (bool) $get('background_pattern_animated'))
                                ->helperText('Hanyut = mengalir terus. Denyut = kepekatannya naik-turun. Ikut Guliran = bergeser hanya saat halaman digulir.'),

                            Select::make('background_pattern_speed')
                                ->label('Kecepatan Gerak')
                                ->options(ContentSection::PATTERN_SPEEDS)
                                ->default(ContentSection::DEFAULT_PATTERN_SPEED)
                                ->required()
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->visible(fn (Get $get): bool => $get('background_pattern') !== 'none'
                                    && (bool) $get('background_pattern_animated')
                                    && $get('background_pattern_motion') !== 'scroll')
                                ->helperText('Laju alir pola dalam piksel per detik. "Ikut Guliran" tidak memakainya karena kecepatannya mengikuti guliran pengunjung.'),
                        ]),

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
     * Satu kelompok pengaturan huruf untuk satu elemen teks. Semua bidangnya
     * boleh kosong; yang kosong berarti elemen itu memakai gaya bawaannya.
     * Elemen kartu hanya tampil saat tata letaknya memang berkartu.
     */
    private static function typographyFieldset(string $element, string $label): Fieldset
    {
        return Fieldset::make($label)
            ->visible(in_array($element, SectionTypography::CARD_ELEMENTS, true)
                ? self::usesCards()
                : true)
            ->columns(2)
            ->schema([
                Select::make("typography.{$element}.align")
                    ->label('Perataan')
                    ->options(SectionTypography::ALIGNMENTS)
                    ->native(false)
                    ->placeholder('Ikut Tata Letak'),

                Select::make("typography.{$element}.weight")
                    ->label('Ketebalan')
                    ->options(SectionTypography::WEIGHTS)
                    ->native(false)
                    ->placeholder('Bawaan'),

                Select::make("typography.{$element}.size")
                    ->label('Ukuran')
                    ->options(SectionTypography::SIZES)
                    ->default('md')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->helperText('Mengecil atau membesar relatif terhadap ukuran bawaannya.'),

                ColorPicker::make("typography.{$element}.color")
                    ->label('Warna Teks')
                    ->hex()
                    ->placeholder('Ikut Tema')
                    ->helperText('Kosongkan agar mengikuti warna tema dan latar seksi.'),
            ]);
    }

    /**
     * Opsi latar gambar hanya relevan saat jenis latarnya "Gambar".
     */
    private static function usesBackgroundImage(): Closure
    {
        return fn (Get $get): bool => $get('background') === 'image';
    }

    private static function usesLayout(string $layout): Closure
    {
        return fn (Get $get): bool => ($get('layout') ?: 'media') === $layout;
    }

    /**
     * Daftar kartu dipakai bersama oleh tata letak kartu dan carousel.
     */
    private static function usesCards(): Closure
    {
        return fn (Get $get): bool => in_array($get('layout'), ['cards', 'carousel'], true);
    }
}
