<?php

namespace App\Filament\Resources\Slides\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithVideoPicker;
use App\Models\Slide;
use App\Services\EmbedVideo;
use App\Support\HeroTitleEffect;
use App\Support\HexColor;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

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

            self::titleEffectSection(),

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

    /**
     * Warna & animasi judul slide ini. Nilainya menghuni satu kolom JSON
     * `title_effect`, jadi seluruh kartunya digantung pada satu `Group` ber-
     * `statePath` dan tiap kolom cukup menyebut namanya sendiri.
     */
    private static function titleEffectSection(): Section
    {
        $hasEffect = fn (Get $get): bool => HeroTitleEffect::sanitizeEffect($get('effect')) !== HeroTitleEffect::DEFAULT_EFFECT;

        return Section::make('Efek Judul')
            ->description('Warna judul slide ini dan animasi yang memainkannya setiap kali slide ini tampil. Tiap slide boleh berbeda.')
            ->icon(Heroicon::OutlinedSparkles)
            ->collapsible()
            ->collapsed()
            ->schema([
                Group::make()
                    ->statePath('title_effect')
                    ->schema([
                        ColorPicker::make('color')
                            ->label('Warna Judul (HEX)')
                            ->live(onBlur: true)
                            ->rule(HexColor::validationRule())
                            ->validationMessages(['regex' => 'Isi dengan kode warna HEX, misalnya #ffffff.'])
                            ->helperText('Kosongkan untuk memakai putih bawaan hero — pilihan teraman di atas foto yang gelap.')
                            ->columnSpanFull(),

                        ToggleButtons::make('effect')
                            ->label('Animasi Judul')
                            ->options(HeroTitleEffect::EFFECTS)
                            ->icons([
                                'none' => Heroicon::OutlinedNoSymbol,
                                'fade-up' => Heroicon::OutlinedArrowUpCircle,
                                'wipe' => Heroicon::OutlinedArrowRightCircle,
                                'typing' => Heroicon::OutlinedCommandLine,
                                'underline' => Heroicon::OutlinedMinus,
                                'highlight' => Heroicon::OutlinedPaintBrush,
                                'border' => Heroicon::OutlinedStop,
                                'gradient' => Heroicon::OutlinedSwatch,
                                'shine' => Heroicon::OutlinedSparkles,
                                'glow' => Heroicon::OutlinedSun,
                            ])
                            ->default(HeroTitleEffect::DEFAULT_EFFECT)
                            ->required()
                            ->live()
                            ->columns(2)
                            ->helperText('Animasi diputar ulang setiap kali slide ini kembali tampil, jadi pengunjung tetap melihatnya walau slide sudah berputar beberapa kali.')
                            ->columnSpanFull(),

                        ColorPicker::make('accent_color')
                            ->label('Warna Aksen Efek (HEX)')
                            ->live(onBlur: true)
                            ->rule(HexColor::validationRule())
                            ->validationMessages(['regex' => 'Isi dengan kode warna HEX, misalnya #d97706.'])
                            ->visible(fn (Get $get): bool => HeroTitleEffect::usesAccent($get('effect')))
                            ->helperText('Warna garis, sapuan stabilo, bingkai, kilau, atau kursor mengetik. Kosongkan untuk mengikuti warna utama tema.')
                            ->columnSpanFull(),

                        Slider::make('duration')
                            ->label('Lama Animasi (milidetik)')
                            ->range(minValue: HeroTitleEffect::MIN_DURATION, maxValue: HeroTitleEffect::MAX_DURATION)
                            ->step(50)
                            ->tooltips()
                            ->default(HeroTitleEffect::DEFAULT_DURATION)
                            ->required()
                            ->live(onBlur: true)
                            ->visible($hasEffect)
                            ->helperText('Lama satu putaran animasi. Pada mesin ketik ini menjadi waktu untuk mengetik judul sampai utuh, jadi judul panjang otomatis diketik lebih cepat.')
                            ->columnSpanFull(),

                        Slider::make('delay')
                            ->label('Jeda Sebelum Mulai (milidetik)')
                            ->range(minValue: HeroTitleEffect::MIN_DELAY, maxValue: HeroTitleEffect::MAX_DELAY)
                            ->step(50)
                            ->tooltips()
                            ->default(HeroTitleEffect::DEFAULT_DELAY)
                            ->required()
                            ->live(onBlur: true)
                            ->visible($hasEffect)
                            ->helperText('Beri jeda kecil bila animasi judul terasa bertabrakan dengan perpindahan slide.')
                            ->columnSpanFull(),

                        Toggle::make('loop')
                            ->label('Ulangi Terus')
                            ->default(true)
                            ->onColor('success')
                            ->live()
                            ->visible($hasEffect)
                            ->helperText('Matikan agar animasi hanya berjalan sekali saat slide muncul, lalu judul diam di tampilan akhirnya.')
                            ->columnSpanFull(),

                        Toggle::make('caret')
                            ->label('Tampilkan Kursor Mengetik')
                            ->default(true)
                            ->onColor('success')
                            ->live()
                            ->visible(fn (Get $get): bool => HeroTitleEffect::sanitizeEffect($get('effect')) === 'typing')
                            ->helperText('Garis kecil berkedip di ujung teks, seperti pada layar terminal.')
                            ->columnSpanFull(),

                        Placeholder::make('preview')
                            ->label('Pratinjau')
                            ->visible($hasEffect)
                            ->content(fn (Get $get): HtmlString => self::titleEffectPreview($get))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Pratinjau judul di atas latar gelap seperti hero sungguhan, memakai judul
     * yang sedang diketik admin. Stylesheet efeknya di-`include` dari partial
     * yang sama dengan halaman depan, jadi tidak ada versi kedua yang bisa
     * melenceng.
     */
    private static function titleEffectPreview(Get $get): HtmlString
    {
        $effect = HeroTitleEffect::fromArray([
            'effect' => $get('effect'),
            'color' => $get('color'),
            'accent_color' => $get('accent_color'),
            'duration' => $get('duration'),
            'delay' => $get('delay'),
            'loop' => $get('loop'),
            'caret' => $get('caret'),
        ]);

        $sample = trim((string) $get('../title')) ?: 'Judul Slide';

        $css = view('partials.hero-title-effects')->render();
        $body = self::titleEffectPreviewBody($effect, $sample);
        $caption = e(HeroTitleEffect::EFFECTS[$effect->effect]).' — '.($effect->loop ? 'diulang terus' : 'sekali jalan')
            .', '.$effect->duration.' ms'.($effect->delay > 0 ? ' setelah jeda '.$effect->delay.' ms' : '').'.';

        return new HtmlString(<<<HTML
            {$css}
            <div style="display: grid; gap: .6rem;">
                <div class="hero-title-preview" style="background: linear-gradient(135deg,#0f172a 0%,#1f2937 55%,#111827 100%); border-radius: .75rem; padding: 2.25rem 1.5rem; overflow: hidden; color: #fff;">
                    <div class="{$effect->cssClass()}" style="{$effect->cssVars()}; font-size: 1.65rem; font-weight: 800; line-height: 1.2; letter-spacing: -.02em;">{$body}</div>
                </div>
                <p style="font-size: .75rem; color: #6e6e73;">{$caption}</p>
            </div>
        HTML);
    }

    /**
     * Isi judul pratinjau. Mesin ketik memakai komponen Alpine mungil miliknya
     * sendiri — bolak-balik mengetik dan menghapus tanpa henti supaya admin
     * selalu melihat geraknya — sementara efek lain cukup teks biasa yang
     * digerakkan CSS.
     */
    private static function titleEffectPreviewBody(HeroTitleEffect $effect, string $sample): string
    {
        $caret = $effect->showsCaret() ? '<span class="hero-title-caret" aria-hidden="true"></span>' : '';

        if (! $effect->isTyping()) {
            return '<span class="hero-title-text">'.e($sample).$caret.'</span>';
        }

        // JSON mentah, bukan `Js::from()`: seluruh atribut baru di-escape sekali
        // di baris terakhir, jadi meng-escape isinya lebih dulu akan bertumpuk.
        $full = json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $speed = $effect->typingSpeedFor($sample);

        $state = "{ full: {$full}, typed: '', at: 0, dir: 1, timer: null,"
            .' init() { this.timer = setInterval(() => {'
            .' this.at += this.dir;'
            .' if (this.at > this.full.length) { this.dir = -1; this.at = this.full.length }'
            .' else if (this.at < 0) { this.dir = 1; this.at = 0 }'
            ." this.typed = this.full.slice(0, this.at) }, {$speed}) },"
            .' destroy() { clearInterval(this.timer) } }';

        return '<span class="hero-title-text" x-data="'.e($state).'"><span x-text="typed"></span>'.$caret.'</span>';
    }
}
