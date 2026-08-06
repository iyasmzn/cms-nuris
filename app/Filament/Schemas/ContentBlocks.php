<?php

namespace App\Filament\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Models\ContentSection;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Shared "Konten Tambahan" blocks repeater used by static pages, posts,
 * programs, events, and stories. Selain blok gambar, tersedia juga bentuk isi
 * yang sama dengan seksi dinamis halaman depan: gambar berdampingan teks,
 * deretan kartu, dan carousel kartu. Every image upload uses the media image
 * picker (choose from library or upload a new file).
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

    public static function make(string $directory): Repeater
    {
        return Repeater::make('blocks')
            ->label('')
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
                    ->live()
                    ->native(false)
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

                Select::make('media_position')
                    ->label('Posisi Gambar')
                    ->options(ContentSection::IMAGE_POSITIONS)
                    ->default('right')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->helperText('Di layar ponsel gambar selalu tampil di atas teks.')
                    ->visible(self::usesType('media_text'))
                    ->columnSpanFull(),

                TextInput::make('heading')
                    ->label('Judul')
                    ->maxLength(150)
                    ->placeholder('Lingkungan Belajar yang Nyaman')
                    ->helperText('Opsional.')
                    ->visible(self::usesType('media_text'))
                    ->columnSpanFull(),

                RichEditor::make('text')
                    ->label('Teks')
                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo'])
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

                Fieldset::make('Pengaturan Carousel')
                    ->columns(2)
                    ->visible(self::usesType('cards_carousel'))
                    ->schema([
                        Toggle::make('carousel_autoplay')
                            ->label('Jalan Sendiri (Autoplay)')
                            ->default(true)
                            ->onColor('success')
                            ->live()
                            ->helperText('Berhenti sementara saat kartunya disentuh atau disorot kursor.'),

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
            ])
            ->addActionLabel('+ Tambah Blok')
            ->reorderable()
            ->collapsible()
            ->collapsed()
            ->defaultItems(0)
            ->itemLabel(fn (array $state): string => match ($state['type'] ?? '') {
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
