<?php

namespace App\Filament\Schemas;

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
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Repeater blok konten. Dipakai dua cara:
 *
 * - `sections: true` (halaman & program) — blok adalah konten utamanya, tiap
 *   blok dirender sebagai seksi tersendiri lengkap dengan latar, judul seksi,
 *   dan anchor, persis seperti seksi dinamis halaman depan.
 * - `sections: false` (artikel, kegiatan, cerita) — blok tetap sebagai konten
 *   tambahan yang mengalir di dalam kartu artikel, tanpa pengaturan seksi.
 *
 * Every image upload uses the media image picker (choose from library or upload
 * a new file).
 */
class ContentBlocks
{
    use InteractsWithImagePicker;

    /**
     * @var list<string>
     */
    private const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Blok yang menampilkan deretan kartu.
     *
     * @var list<string>
     */
    private const CARD_TYPES = ['cards', 'cards_carousel'];

    /**
     * Jarak atas-bawah seksi → labelnya di panel.
     *
     * @var array<string, string>
     */
    public const PADDINGS = [
        'none' => 'Tanpa Jarak',
        'sm' => 'Rapat',
        'md' => 'Sedang',
        'lg' => 'Lega',
    ];

    /**
     * @param  string  $directory  folder penyimpanan gambar blok
     * @param  bool  $sections  blok berdiri sebagai seksi sendiri (konten utama)
     */
    public static function make(string $directory, bool $sections = false): Repeater
    {
        return Repeater::make('blocks')
            ->label('')
            // Satu bidang per baris: kartu blok memakai lebar formulir seutuhnya.
            ->columns(1)
            ->schema([
                Select::make('type')
                    ->label('Jenis Blok')
                    ->options([
                        'rich_text' => '📄  Teks — paragraf, judul, daftar, tabel',
                        'image_cover' => '🖼️  Cover Image — satu gambar penuh lebar',
                        'image_carousel' => '🎠  Carousel — slider beberapa gambar',
                        'image_gallery' => '🖼️  Galeri — grid beberapa gambar',
                        'cta_button' => '🔘  Tombol CTA — tombol ajakan bertindak',
                        'media_text' => '📝  Gambar & Teks — gambar berdampingan teks',
                        'cards' => '🧩  Deretan Kartu — beberapa kartu dalam grid',
                        'cards_carousel' => '🎡  Carousel Kartu — kartu yang berjalan',
                    ])
                    ->required()
                    ->default('rich_text')
                    ->live()
                    ->native(false)
                    ->columnSpanFull(),

                // ── Judul seksi ───────────────────────────────
                TextInput::make('eyebrow')
                    ->label('Label Kecil')
                    ->maxLength(60)
                    ->placeholder('Fasilitas Kami')
                    ->helperText('Teks kecil di atas judul seksi. Kosongkan untuk menyembunyikan.')
                    ->visible($sections)
                    ->columnSpanFull(),

                TextInput::make('heading')
                    ->label($sections ? 'Judul Seksi' : 'Judul')
                    ->maxLength(150)
                    ->placeholder('Lingkungan Belajar yang Nyaman')
                    ->helperText('Opsional.')
                    ->visible($sections ? true : self::usesType('media_text'))
                    ->columnSpanFull(),

                // ── Teks ──────────────────────────────────────
                RichEditor::make('content')
                    ->hiddenLabel()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory($directory.'/attachments')
                    ->fileAttachmentsVisibility('public')
                    ->placeholder('Tulis isi teksnya di sini...')
                    // Editor menyisakan <p></p> saat isinya dihapus; simpan sebagai kosong
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled(trim(strip_tags((string) $state)))
                        ? $state
                        : null)
                    ->visible(self::usesType('rich_text'))
                    ->columnSpanFull(),

                // ── Cover Image ──────────────────────────────
                self::imagePicker(
                    key: 'image',
                    label: 'Gambar',
                    hint: 'Lebar optimal 1400px atau lebih.',
                    accepted: self::ACCEPTED,
                    width: 1400,
                    directory: $directory,
                    withMeta: false,
                )->visible(fn (Get $get): bool => $get('type') === 'image_cover'),

                TextInput::make('caption')
                    ->label('Keterangan Gambar')
                    ->maxLength(200)
                    ->placeholder('Opsional — keterangan singkat di bawah gambar')
                    ->visible(fn (Get $get): bool => $get('type') === 'image_cover')
                    ->columnSpanFull(),

                // ── Carousel & Gallery — shared images repeater ──
                Repeater::make('images')
                    ->label('Daftar Gambar')
                    ->schema([
                        self::imagePicker(
                            key: 'image',
                            label: 'Gambar',
                            hint: 'Lebar optimal 1400px atau lebih.',
                            accepted: self::ACCEPTED,
                            width: 1600,
                            directory: $directory,
                            withMeta: false,
                        ),

                        TextInput::make('caption')
                            ->label('Keterangan')
                            ->maxLength(200)
                            ->placeholder('Opsional')
                            ->columnSpanFull(),
                    ])
                    ->addActionLabel('+ Tambah Gambar')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->reorderable()
                    ->collapsed(false)
                    ->itemLabel(fn (array $state): string => $state['caption'] ?: 'Gambar')
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['image_carousel', 'image_gallery']))
                    ->columnSpanFull(),

                // ── Gallery columns selector ──────────────────
                Select::make('columns')
                    ->label('Jumlah Kolom')
                    ->options(['2' => '2 Kolom', '3' => '3 Kolom', '4' => '4 Kolom'])
                    ->default('3')
                    ->native(false)
                    ->visible(fn (Get $get): bool => $get('type') === 'image_gallery'),

                // ── CTA Button ────────────────────────────────
                TextInput::make('label')
                    ->label('Teks Tombol')
                    ->maxLength(100)
                    ->required(fn (Get $get): bool => $get('type') === 'cta_button')
                    ->placeholder('Daftar Sekarang')
                    ->visible(fn (Get $get): bool => $get('type') === 'cta_button')
                    ->columnSpanFull(),

                TextInput::make('url')
                    ->label('URL / Link')
                    ->url()
                    ->maxLength(500)
                    ->required(fn (Get $get): bool => $get('type') === 'cta_button')
                    ->placeholder('https://contoh.sch.id/pendaftaran')
                    ->visible(fn (Get $get): bool => $get('type') === 'cta_button')
                    ->columnSpanFull(),

                Select::make('style')
                    ->label('Gaya Tombol')
                    ->options([
                        'primary' => 'Utama (solid)',
                        'outline' => 'Garis (outline)',
                    ])
                    ->default('primary')
                    ->native(false)
                    ->visible(fn (Get $get): bool => $get('type') === 'cta_button'),

                ColorPicker::make('color')
                    ->label('Warna Tombol')
                    ->placeholder('Kosongkan untuk warna tema')
                    ->hint('Opsional — kosongkan untuk memakai warna utama tema.')
                    ->visible(fn (Get $get): bool => $get('type') === 'cta_button'),

                Select::make('alignment')
                    ->label('Perataan')
                    ->options([
                        'left' => 'Kiri',
                        'center' => 'Tengah',
                        'right' => 'Kanan',
                    ])
                    ->default('center')
                    ->native(false)
                    ->visible(fn (Get $get): bool => $get('type') === 'cta_button'),

                Toggle::make('open_in_new_tab')
                    ->label('Buka di Tab Baru')
                    ->default(false)
                    ->visible(fn (Get $get): bool => $get('type') === 'cta_button')
                    ->columnSpanFull(),

                // ── Gambar & Teks ─────────────────────────────
                self::imagePicker(
                    key: 'media_image',
                    label: 'Gambar',
                    hint: 'Rasio 4:3. Akan di-resize ke 1000×750. Kosongkan bila blok ini tampil tanpa gambar.',
                    accepted: self::ACCEPTED,
                    width: 1000,
                    height: 750,
                    directory: $directory,
                    aspectRatio: '4:3',
                    withMeta: false,
                )
                    ->visible(self::usesType('media_text'))
                    ->columnSpanFull(),

                Select::make('text_align')
                    ->label('Perataan Teks')
                    ->options(SectionTypography::ALIGNMENTS)
                    ->default('left')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->helperText('Berlaku untuk judul, teks, dan tombol pada blok ini. Perataan per paragraf tetap bisa diatur lewat tombol perataan di editor.')
                    ->visible(self::usesType('media_text'))
                    ->columnSpanFull(),

                Select::make('media_position')
                    ->label('Posisi Gambar')
                    ->options(ContentSection::IMAGE_POSITIONS)
                    ->default('right')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->helperText('Di layar ponsel gambar selalu tampil di atas teks.')
                    ->visible(self::usesType('media_text'))
                    ->columnSpanFull(),

                RichEditor::make('text')
                    ->label('Teks')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'link'],
                        // Judul seksi sudah memakai h2, jadi h2–h4 di sini untuk subjudul
                        ['h2', 'h3', 'h4'],
                        ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                        ['blockquote', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->placeholder('Jelaskan bagian ini dalam beberapa kalimat...')
                    // Editor menyisakan <p></p> saat isinya dihapus; simpan sebagai kosong
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled(trim(strip_tags((string) $state)))
                        ? $state
                        : null)
                    ->visible(self::usesType('media_text'))
                    ->columnSpanFull(),

                Fieldset::make('Tombol')
                    ->columns(2)
                    ->visible(self::usesType('media_text'))
                    ->schema([
                        TextInput::make('cta_label')
                            ->label('Teks Tombol')
                            ->maxLength(60)
                            ->placeholder('Selengkapnya')
                            ->helperText('Kosongkan bila blok ini tanpa tombol.')
                            ->requiredWith('cta_url'),

                        TextInput::make('cta_url')
                            ->label('Tautan Tombol')
                            ->maxLength(255)
                            ->placeholder('https://... atau /program')
                            ->helperText('Boleh URL lengkap atau path internal seperti /profil.')
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

                // ── Kartu & Carousel Kartu ────────────────────
                Repeater::make('items')
                    ->label('Daftar Kartu')
                    ->addActionLabel('+ Tambah Kartu')
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): string => $state['title'] ?? 'Kartu baru')
                    ->defaultItems(1)
                    ->schema([
                        self::imagePicker(
                            key: 'image',
                            label: 'Gambar Kartu',
                            hint: 'Rasio 4:3. Akan di-resize ke 800×600. Kosongkan bila kartu tampil tanpa gambar.',
                            accepted: self::ACCEPTED,
                            width: 800,
                            height: 600,
                            directory: $directory.'/cards',
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
                    ->visible(self::usesCards())
                    ->columnSpanFull(),

                Select::make('items_columns')
                    ->label('Kartu Sebaris')
                    ->options(ContentSection::ITEM_COLUMNS)
                    ->default(3)
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->helperText('Berlaku di layar lebar. Layar ponsel selalu satu kartu, layar sedang paling banyak dua.')
                    ->visible(self::usesCards())
                    ->columnSpanFull(),

                Select::make('items_ratio')
                    ->label('Ratio Gambar Kartu')
                    ->options(ContentSection::CARD_RATIOS)
                    ->default(ContentSection::DEFAULT_CARD_RATIO)
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->helperText('Bentuk area gambar di setiap kartu. Gambar dipotong menyesuaikan bidangnya, jadi tinggi gambar seragam meski ukuran aslinya berbeda-beda. Kartu tanpa gambar tidak terpengaruh.')
                    ->visible(self::usesCards())
                    ->columnSpanFull(),

                Fieldset::make('Pengaturan Carousel')
                    ->columns(2)
                    ->visible(self::usesType('cards_carousel'))
                    ->schema([
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

                // ── Pengaturan seksi ──────────────────────────
                ...($sections ? self::sectionSettings($directory) : []),
            ])
            ->addActionLabel($sections ? '+ Tambah Seksi' : '+ Tambah Blok')
            ->reorderable()
            ->collapsible()
            ->collapsed()
            // Halaman baru langsung terbuka dengan satu seksi teks siap diisi,
            // jenisnya tetap bisa diganti.
            ->defaultItems($sections ? 1 : 0)
            // Seksi yang sudah diberi judul dikenali dari judulnya, bukan jenisnya
            ->itemLabel(fn (array $state): string => filled($state['heading'] ?? null)
                ? self::icon($state['type'] ?? '').'  '.$state['heading']
                : match ($state['type'] ?? '') {
                    'rich_text' => '📄  Teks'.self::excerpt($state['content'] ?? null),
                    'image_cover' => '🖼️  Cover Image',
                    'image_carousel' => '🎠  Carousel — '.count($state['images'] ?? []).' gambar',
                    'image_gallery' => '🖼️🖼️  Galeri — '.count($state['images'] ?? []).' gambar',
                    'cta_button' => '🔘  Tombol CTA'.(! empty($state['label']) ? ' — '.$state['label'] : ''),
                    'media_text' => '📝  Gambar & Teks'.(! empty($state['heading']) ? ' — '.$state['heading'] : ''),
                    'cards' => '🧩  Deretan Kartu — '.count($state['items'] ?? []).' kartu',
                    'cards_carousel' => '🎡  Carousel Kartu — '.count($state['items'] ?? []).' kartu',
                    default => 'Blok Baru',
                })
            ->columnSpanFull();
    }

    /**
     * Emoji penanda jenis blok, dipakai pada label blok yang sudah berjudul.
     */
    private static function icon(string $type): string
    {
        return match ($type) {
            'rich_text' => '📄',
            'image_cover' => '🖼️',
            'image_carousel' => '🎠',
            'image_gallery' => '🖼️🖼️',
            'cta_button' => '🔘',
            'media_text' => '📝',
            'cards' => '🧩',
            'cards_carousel' => '🎡',
            default => '📦',
        };
    }

    /**
     * Pengaturan yang membuat satu blok berdiri sebagai seksi: anchor, jarak,
     * dan latar belakang — bentuknya sama dengan seksi dinamis halaman depan.
     *
     * @return list<Component>
     */
    private static function sectionSettings(string $directory): array
    {
        return [
            Section::make('Latar & Tampilan Seksi')
                ->description('Latar abu lembut, putih bersih, atau gambar penuh dengan blur, lapisan gelap, dan parallax.')
                ->icon(Heroicon::OutlinedSwatch)
                ->collapsible()
                ->collapsed()
                ->columns(1)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('background')
                            ->label('Jenis Latar')
                            ->options(ContentSection::BACKGROUNDS)
                            ->default('default')
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->live()
                            ->helperText('Selang-seling abu lembut dan putih bersih agar antar seksi tidak menyatu.'),

                        Select::make('padding')
                            ->label('Jarak Atas-Bawah')
                            ->options(self::PADDINGS)
                            ->default('md')
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->helperText('Ruang kosong di atas dan bawah isi seksi.'),

                        Select::make('heading_align')
                            ->label('Perataan Judul Seksi')
                            ->options(SectionTypography::ALIGNMENTS)
                            ->default('left')
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->helperText('Berlaku untuk label kecil dan judul seksi, apa pun jenis bloknya.')
                            ->columnSpanFull(),
                    ]),

                    Grid::make(2)
                        ->visible(fn (Get $get): bool => $get('background') !== 'image')
                        ->schema([
                            Select::make('background_pattern')
                                ->label('Pola Latar')
                                ->options(SectionPatterns::selectOptions())
                                ->allowHtml()
                                ->default('none')
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->live()
                                ->helperText('Hiasan SVG halus di atas warna dasar seksi.'),

                            Select::make('background_pattern_opacity')
                                ->label('Kepekatan Pola')
                                ->options(ContentSection::PATTERN_OPACITY_LEVELS)
                                ->default(ContentSection::DEFAULT_PATTERN_OPACITY)
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->visible(self::usesBackgroundPattern())
                                ->helperText('Makin pekat makin terlihat. Pola yang terlalu kuat membuat teks susah dibaca.'),

                            Select::make('background_pattern_scale')
                                ->label('Ukuran Pola')
                                ->options(ContentSection::PATTERN_SCALES)
                                ->default(ContentSection::DEFAULT_PATTERN_SCALE)
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->visible(self::usesBackgroundPattern())
                                ->helperText('Ukuran ubin polanya. Makin rapat, makin ramai.'),

                            Select::make('background_pattern_color')
                                ->label('Warna Pola')
                                ->options(ContentSection::PATTERN_COLORS)
                                ->default(ContentSection::DEFAULT_PATTERN_COLOR)
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->live()
                                ->visible(self::usesBackgroundPattern())
                                ->helperText('Pilihan bertoken ikut berubah sendiri saat warna tema diganti.'),

                            ColorPicker::make('background_pattern_custom_color')
                                ->label('Hex Warna Pola')
                                ->default('#08484A')
                                ->visible(fn (Get $get): bool => $get('background') !== 'image'
                                    && ($get('background_pattern') ?? 'none') !== 'none'
                                    && $get('background_pattern_color') === 'custom')
                                ->helperText('Kosong atau tidak berbentuk hex akan kembali ke warna utama tema.')
                                ->columnSpanFull(),

                            Toggle::make('background_pattern_animated')
                                ->label('Animasikan Pola')
                                ->default(false)
                                ->onColor('success')
                                ->live()
                                ->visible(self::usesBackgroundPattern())
                                ->helperText('Pola bergerak pelan. Otomatis padam bagi pengunjung yang menyetel perangkatnya untuk mengurangi gerak.')
                                ->columnSpanFull(),

                            Select::make('background_pattern_motion')
                                ->label('Jenis Gerak')
                                ->options(ContentSection::PATTERN_MOTIONS)
                                ->default(ContentSection::DEFAULT_PATTERN_MOTION)
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->live()
                                ->visible(fn (Get $get): bool => $get('background') !== 'image'
                                    && ($get('background_pattern') ?? 'none') !== 'none'
                                    && (bool) $get('background_pattern_animated'))
                                ->helperText('Hanyut = mengalir terus. Denyut = kepekatannya naik-turun. Ikut Guliran = bergeser saat digulir.'),

                            Select::make('background_pattern_speed')
                                ->label('Kecepatan Gerak')
                                ->options(ContentSection::PATTERN_SPEEDS)
                                ->default(ContentSection::DEFAULT_PATTERN_SPEED)
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->visible(fn (Get $get): bool => $get('background') !== 'image'
                                    && ($get('background_pattern') ?? 'none') !== 'none'
                                    && (bool) $get('background_pattern_animated')
                                    && $get('background_pattern_motion') !== 'scroll')
                                ->helperText('Laju alir pola dalam piksel per detik.'),
                        ]),

                    self::imagePicker(
                        key: 'background_image',
                        label: 'Gambar Latar',
                        hint: 'Gambar lebar (landscape). Akan di-resize ke 1920×1080.',
                        accepted: self::ACCEPTED,
                        width: 1920,
                        height: 1080,
                        directory: $directory.'/backgrounds',
                        aspectRatio: '16:9',
                        withMeta: false,
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
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->helperText('Mengaburkan gambar agar teks lebih mudah dibaca.'),

                            Select::make('background_overlay')
                                ->label('Lapisan Gelap')
                                ->options(ContentSection::OVERLAY_LEVELS)
                                ->default(0)
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->helperText('Menggelapkan gambar agar kontras teks meningkat.'),

                            Toggle::make('background_light_text')
                                ->label('Teks Warna Terang')
                                ->default(true)
                                ->onColor('success')
                                ->helperText('Nyalakan bila latarnya gelap, matikan bila latarnya terang.')
                                ->columnSpanFull(),

                            Select::make('background_parallax_mode')
                                ->label('Gerak Saat Digulir')
                                ->options(ContentSection::PARALLAX_MODES)
                                ->default('none')
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->live()
                                ->helperText('Bergeser: gambar tertinggal di belakang konten. Diam: gambar terkunci ke layar.')
                                ->columnSpanFull(),

                            Slider::make('background_parallax_speed')
                                ->label('Kekuatan Parallax')
                                ->range(minValue: ContentSection::PARALLAX_MIN_SPEED, maxValue: ContentSection::PARALLAX_MAX_SPEED)
                                ->step(1)
                                ->tooltips()
                                ->default(30)
                                ->visible(fn (Get $get): bool => $get('background_parallax_mode') === 'scroll')
                                ->helperText('Makin tinggi, gambar latar makin diperbesar agar tepinya tidak menyingkap.')
                                ->columnSpanFull(),
                        ]),

                    TextInput::make('anchor')
                        ->label('ID Anchor')
                        ->maxLength(60)
                        ->placeholder('fasilitas')
                        ->helperText('Untuk tautan menu, misal #fasilitas. Kosongkan bila tidak perlu.')
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Opsi latar gambar hanya relevan saat jenis latarnya "Gambar".
     */
    private static function usesBackgroundImage(): Closure
    {
        return fn (Get $get): bool => $get('background') === 'image';
    }

    /**
     * Pola hanya berlaku di latar non-gambar, dan setelan turunannya baru
     * muncul setelah polanya dipilih.
     *
     * @return Closure(Get): bool
     */
    private static function usesBackgroundPattern(): Closure
    {
        return fn (Get $get): bool => $get('background') !== 'image'
            && ($get('background_pattern') ?? 'none') !== 'none';
    }

    /**
     * Cuplikan singkat isi teks untuk label blok yang tertutup.
     */
    private static function excerpt(?string $html): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));

        return $text === '' ? '' : ' — '.str($text)->limit(50);
    }

    private static function usesType(string $type): Closure
    {
        return fn (Get $get): bool => $get('type') === $type;
    }

    /**
     * Daftar kartu dipakai bersama oleh blok kartu dan carousel kartu.
     */
    private static function usesCards(): Closure
    {
        return fn (Get $get): bool => in_array($get('type'), self::CARD_TYPES, true);
    }
}
