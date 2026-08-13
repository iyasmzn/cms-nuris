<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\ContentSections\ContentSectionResource;
use App\Models\ContentSection;
use App\Models\Setting;
use App\Support\AlumniMarquee;
use App\Support\HeroSlider;
use App\Support\SectionBackground;
use App\Support\SectionPatterns;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class LandingPageSettings extends Page
{
    use HasPageShield;
    use InteractsWithImagePicker;

    protected string $view = 'filament.pages.general-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Halaman Depan';

    protected static ?string $title = 'Pengaturan Halaman Depan';

    protected static ?int $navigationSort = 3;

    /**
     * Awalan kunci seksi bawaan di dalam `section_order`.
     */
    private const BUILT_IN_PREFIX = 'section_';

    /**
     * Kunci seksi hero — satu-satunya seksi bawaan yang punya pengaturan
     * slider di kartunya.
     */
    private const HERO_KEY = 'section_hero';

    /**
     * Kunci seksi alumni — satu-satunya seksi bawaan yang punya pengaturan
     * baris logo berjalan di kartunya.
     */
    private const ALUMNI_KEY = 'section_alumni';

    /**
     * Seksi bawaan yang latarnya tidak bisa diatur di sini: hero sudah punya
     * gambar/video slidernya sendiri di menu Slide.
     *
     * @var list<string>
     */
    private const WITHOUT_BACKGROUND = ['section_hero'];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /**
     * Seksi bawaan ditambah seksi dinamis (ContentSection) yang dibuat admin,
     * sehingga keduanya bisa diurutkan dalam satu daftar.
     *
     * @return array<int, array{key: string, label: string, anchor: string, visible: bool}>
     */
    private static function defaultSections(): array
    {
        $sections = array_map(
            fn (array $section): array => [
                ...$section,
                'anchor' => self::BUILT_IN_ANCHORS[$section['key']] ?? '',
            ],
            self::builtInSections(),
        );

        foreach (ContentSection::ordered()->get() as $contentSection) {
            $sections[] = [
                'key' => $contentSection->order_key,
                'label' => '🧩  '.$contentSection->title,
                'anchor' => $contentSection->anchor_id,
                'visible' => $contentSection->is_published,
            ];
        }

        return $sections;
    }

    /** @return array<int, array{key: string, label: string, anchor: string, visible: bool}> */
    private static function builtInSections(): array
    {
        return [
            ['key' => 'section_hero',        'label' => '🖼️  Hero Image Slider',        'visible' => true],
            ['key' => 'section_quick_links', 'label' => '🔗  Tautan Cepat',              'visible' => true],
            ['key' => 'section_spmb',        'label' => '📋  Kartu SPMB',               'visible' => true],
            ['key' => 'section_stats',       'label' => '📊  Statistik Sekolah',         'visible' => true],
            ['key' => 'section_principal',   'label' => '👨‍💼  Sambutan Para Tokoh',     'visible' => true],
            ['key' => 'section_spmb_steps',  'label' => '📝  Tahapan SPMB',             'visible' => true],
            ['key' => 'section_programs',    'label' => '🎓  Program Unggulan',          'visible' => true],
            ['key' => 'section_events',      'label' => '📅  Agenda Kegiatan',           'visible' => true],
            ['key' => 'section_gallery',     'label' => '🖼️  Galeri Foto',              'visible' => true],
            ['key' => 'section_blog',        'label' => '📰  Blog & Berita',             'visible' => true],
            ['key' => 'section_alumni',      'label' => '🎓  Jejak Alumni',              'visible' => true],
            ['key' => 'section_testimonials', 'label' => '💬  Kesan & Pesan Alumni',      'visible' => true],
            ['key' => 'section_faq',         'label' => '❓  Pertanyaan Umum (FAQ)',     'visible' => true],
            ['key' => 'section_contact',     'label' => '📞  Kontak Kami',               'visible' => true],
        ];
    }

    /**
     * ID anchor tiap seksi bawaan — harus sama persis dengan atribut `id` pada
     * partial Blade-nya di `resources/views/sections/`, sebab nilainya dipakai
     * admin sebagai tujuan tautan menu (`#profil`).
     *
     * @var array<string, string>
     */
    private const BUILT_IN_ANCHORS = [
        'section_hero' => 'beranda',
        'section_quick_links' => 'tautan-cepat',
        'section_spmb' => 'spmb',
        'section_stats' => 'profil',
        'section_principal' => 'sambutan',
        'section_spmb_steps' => 'tahapan-spmb',
        'section_programs' => 'program',
        'section_events' => 'kegiatan-upcoming',
        'section_gallery' => 'galeri',
        'section_blog' => 'blog',
        'section_alumni' => 'alumni',
        'section_testimonials' => 'kesan-pesan',
        'section_faq' => 'faq',
        'section_contact' => 'kontak-section',
    ];

    /**
     * Sections whose header text (eyebrow, title, subtitle) can be edited here.
     * Keys map to the `section_{key}_*` setting keys read by the Blade partials.
     * `highlight` marks a section whose title has an accented second line, and
     * `extra` declares additional single-line texts unique to a section (stored
     * as `section_{key}_{suffix}`).
     *
     * @return array<string, array{label: string, icon: Heroicon, eyebrow: string, title: string, subtitle: string, highlight?: string, extra?: array<string, array{label: string, default: string, helperText?: string}>}>
     */
    private static function contentSections(): array
    {
        return [
            'programs' => [
                'label' => 'Program Unggulan',
                'icon' => Heroicon::OutlinedAcademicCap,
                'eyebrow' => 'Keunggulan Kami',
                'title' => 'Program Unggulan',
                'subtitle' => 'Berbagai program yang dirancang untuk membentuk santri berprestasi dan berakhlak mulia.',
            ],
            'events' => [
                'label' => 'Agenda Kegiatan',
                'icon' => Heroicon::OutlinedCalendarDays,
                'eyebrow' => 'Agenda Pesantren',
                'title' => 'Kegiatan Akan Datang',
                'subtitle' => 'Pengajian, seminar, dan berbagai kegiatan menarik yang segera diselenggarakan.',
            ],
            'gallery' => [
                'label' => 'Galeri Foto',
                'icon' => Heroicon::OutlinedPhoto,
                'eyebrow' => 'Foto & Video',
                'title' => 'Galeri Sekolah',
                'subtitle' => 'Momen-momen berharga dari kehidupan sekolah kami.',
            ],
            'blog' => [
                'label' => 'Blog & Berita',
                'icon' => Heroicon::OutlinedNewspaper,
                'eyebrow' => 'Berita & Artikel',
                'title' => 'Artikel',
                'subtitle' => 'Blog inspiratif dari berbagai sumber.',
            ],
            'alumni' => [
                'label' => 'Jejak Alumni',
                'icon' => Heroicon::OutlinedAcademicCap,
                'eyebrow' => 'Jejak Alumni',
                'title' => 'Ke Mana Alumni Kami Melangkah',
                'subtitle' => 'Lulusan kami melanjutkan studi dan berkarya di berbagai perguruan tinggi dalam dan luar negeri.',
                'extra' => [
                    'logos_title' => [
                        'label' => 'Judul Baris Logo Kampus',
                        'default' => 'Kampus Tujuan Alumni',
                        'helperText' => 'Teks kecil di atas deretan logo kampus yang berjalan.',
                    ],
                ],
            ],
            'testimonials' => [
                'label' => 'Kesan & Pesan Alumni',
                'icon' => Heroicon::OutlinedChatBubbleBottomCenterText,
                'eyebrow' => 'Kesan & Pesan',
                'title' => 'Apa Kata Alumni',
                'subtitle' => 'Cerita dan harapan dari para alumni yang telah menempuh pendidikan bersama kami.',
            ],
            'faq' => [
                'label' => 'Pertanyaan Umum (FAQ)',
                'icon' => Heroicon::OutlinedQuestionMarkCircle,
                'eyebrow' => 'Pertanyaan Umum',
                'title' => 'Ada yang Ingin Ditanyakan?',
                'subtitle' => 'Jawaban atas pertanyaan yang paling sering diajukan calon santri dan orang tua.',
            ],
            'principal' => [
                'label' => 'Sambutan Para Tokoh',
                'icon' => Heroicon::OutlinedChatBubbleLeftRight,
                'eyebrow' => 'Sambutan',
                'title' => 'Sambutan Para Tokoh',
                'subtitle' => '',
            ],
            'contact' => [
                'label' => 'Kontak Kami',
                'icon' => Heroicon::OutlinedPhone,
                'eyebrow' => 'Hubungi Kami',
                'title' => 'Kami Siap Membantu Anda',
                'subtitle' => 'Punya pertanyaan seputar SPMB, akademik, atau kegiatan sekolah? Jangan ragu untuk menghubungi kami.',
            ],
        ];
    }

    /**
     * Konfigurasi teks yang bisa diubah, di-rekey memakai kunci urutan
     * (`section_programs`) agar cocok dengan isi repeater.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function editableSections(): array
    {
        $sections = [];

        foreach (self::contentSections() as $key => $content) {
            $sections[self::BUILT_IN_PREFIX.$key] = $content;
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function contentConfig(?string $key): ?array
    {
        return self::editableSections()[$key] ?? null;
    }

    private static function isDynamic(?string $key): bool
    {
        return $key !== null && ContentSection::idFromOrderKey($key) !== null;
    }

    /**
     * Seksi bawaan mana yang latarnya bisa diatur dari halaman ini. Seksi
     * dinamis punya pengaturan latar sendiri di halaman editnya.
     */
    private static function supportsBackground(?string $key): bool
    {
        return $key !== null
            && str_starts_with($key, self::BUILT_IN_PREFIX)
            && ! in_array($key, self::WITHOUT_BACKGROUND, true);
    }

    /**
     * Semua teks tambahan yang dideklarasikan seksi mana pun, dipakai untuk
     * membuat satu kolom per suffix di dalam repeater.
     *
     * @return array<string, array{owner: string, label: string, default: string, helperText?: string}>
     */
    private static function extraFields(): array
    {
        $fields = [];

        foreach (self::editableSections() as $key => $content) {
            foreach ($content['extra'] ?? [] as $suffix => $extra) {
                $fields[$suffix] = [...$extra, 'owner' => $key];
            }
        }

        return $fields;
    }

    public function mount(): void
    {
        $defaults = self::defaultSections();
        $labelMap = collect($defaults)->keyBy('key');

        $saved = Setting::get('section_order');
        $savedSections = $saved ? (json_decode($saved, true) ?: []) : [];

        // Preserve the saved order and visibility for known sections, always
        // refreshing the label from the canonical list. Seksi dinamis memakai
        // status publikasi record-nya sebagai satu-satunya sumber kebenaran.
        $sections = [];
        $seen = [];
        foreach ($savedSections as $section) {
            $key = $section['key'] ?? null;
            if (! $key || ! $labelMap->has($key)) {
                continue;
            }

            $sections[] = $this->sectionItem(
                $labelMap->get($key),
                self::isDynamic($key) ? null : (bool) ($section['visible'] ?? true),
            );
            $seen[$key] = true;
        }

        // Append any sections added after the saved order was stored so they
        // remain toggleable (e.g. the gallery section).
        foreach ($defaults as $section) {
            if (! isset($seen[$section['key']])) {
                $sections[] = $this->sectionItem($section);
            }
        }

        $this->form->fill([
            'sections' => $sections,
            'home_meta_title' => Setting::get('home_meta_title', ''),
            'home_meta_description' => Setting::get('home_meta_description', ''),
        ]);
    }

    /**
     * Satu baris repeater: identitas seksi plus teksnya yang sedang tayang,
     * dengan teks bawaan sebagai cadangan agar admin melihat apa yang live.
     *
     * @param  array{key: string, label: string, anchor: string, visible: bool}  $section
     * @return array<string, mixed>
     */
    private function sectionItem(array $section, ?bool $visible = null): array
    {
        $item = [
            'key' => $section['key'],
            'label' => $section['label'],
            'anchor' => $section['anchor'] ?? '',
            'visible' => $visible ?? $section['visible'],
        ];

        if (self::supportsBackground($section['key'])) {
            $item = [...$item, ...self::backgroundState($section['key'])];
        }

        if ($section['key'] === self::HERO_KEY) {
            $item = [...$item, ...self::heroSliderState()];
        }

        if ($section['key'] === self::ALUMNI_KEY) {
            $item = [...$item, ...self::alumniMarqueeState()];
        }

        $config = self::contentConfig($section['key']);

        if ($config === null) {
            return $item;
        }

        $prefix = $section['key'];

        $item['eyebrow'] = Setting::get("{$prefix}_eyebrow", $config['eyebrow']);
        $item['title'] = Setting::get("{$prefix}_title", $config['title']);
        $item['subtitle'] = Setting::get("{$prefix}_subtitle", $config['subtitle']);

        if (isset($config['highlight'])) {
            $item['title_highlight'] = Setting::get("{$prefix}_title_highlight", $config['highlight']);
        }

        foreach ($config['extra'] ?? [] as $suffix => $extra) {
            $item["extra_{$suffix}"] = Setting::get("{$prefix}_{$suffix}", $extra['default']);
        }

        return $item;
    }

    /**
     * Perilaku slider hero yang sedang tayang.
     *
     * @return array<string, mixed>
     */
    private static function heroSliderState(): array
    {
        $hero = HeroSlider::fromSettings();

        return [
            'hero_autoplay' => $hero->autoplay,
            'hero_pause_on_hover' => $hero->pauseOnHover,
            'hero_interval' => $hero->interval,
            'hero_transition' => $hero->transition,
            'hero_transition_duration' => $hero->duration,
        ];
    }

    /**
     * Perilaku & ukuran baris logo kampus yang sedang tayang.
     *
     * @return array<string, mixed>
     */
    private static function alumniMarqueeState(): array
    {
        $marquee = AlumniMarquee::fromSettings();

        return [
            'alumni_autoplay' => $marquee->autoplay,
            'alumni_pause_on_hover' => $marquee->pauseOnHover,
            'alumni_speed' => $marquee->speed,
            'alumni_direction' => $marquee->direction,
            'alumni_logo_height' => $marquee->logoHeight,
            'alumni_card_width' => $marquee->cardWidth,
            'alumni_gap' => $marquee->gap,
            'alumni_grayscale' => $marquee->grayscale,
            'alumni_show_name' => $marquee->showName,
        ];
    }

    /**
     * Latar seksi yang sedang tayang, dipetakan ke nama kolom di dalam repeater.
     *
     * @return array<string, mixed>
     */
    private static function backgroundState(string $key): array
    {
        return [
            'background' => Setting::get("{$key}_background") ?: 'default',
            'background_image' => Setting::get("{$key}_background_image"),
            'background_image_source' => 'upload',
            'background_blur' => (int) Setting::get("{$key}_background_blur", 0),
            'background_overlay' => (int) Setting::get("{$key}_background_overlay", 0),
            'background_light_text' => setting_bool("{$key}_background_light_text", true),
            'background_parallax_mode' => Setting::get("{$key}_background_parallax_mode") ?: 'none',
            'background_parallax_speed' => (int) Setting::get("{$key}_background_parallax_speed", 30),
            'background_pattern' => Setting::get("{$key}_background_pattern") ?: 'none',
            'background_pattern_opacity' => (int) Setting::get("{$key}_background_pattern_opacity", ContentSection::DEFAULT_PATTERN_OPACITY),
            'background_pattern_scale' => (int) Setting::get("{$key}_background_pattern_scale", ContentSection::DEFAULT_PATTERN_SCALE),
            'background_pattern_color' => Setting::get("{$key}_background_pattern_color") ?: ContentSection::DEFAULT_PATTERN_COLOR,
            'background_pattern_custom_color' => Setting::get("{$key}_background_pattern_custom_color"),
            'background_pattern_animated' => setting_bool("{$key}_background_pattern_animated", false),
            'background_pattern_motion' => Setting::get("{$key}_background_pattern_motion") ?: ContentSection::DEFAULT_PATTERN_MOTION,
            'background_pattern_speed' => (int) Setting::get("{$key}_background_pattern_speed", ContentSection::DEFAULT_PATTERN_SPEED),
        ];
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Susunan Halaman Depan')
                ->description('Seret kartu untuk mengubah urutan seksi, dan buka kartunya untuk mengubah teks judul & deskripsi seksi itu. Seksi bertanda 🧩 dibuat di menu Konten → Seksi Halaman Depan.')
                ->icon(Heroicon::OutlinedQueueList)
                ->schema([
                    Repeater::make('sections')
                        ->hiddenLabel()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderableWithDragAndDrop(true)
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => self::itemLabel($state))
                        ->schema($this->sectionItemSchema()),
                ]),

            Section::make('SEO Halaman Depan')
                ->description('Judul dan deskripsi meta khusus halaman depan untuk mesin pencari (Google) dan berbagi ke media sosial.')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('home_meta_title')
                        ->label('Meta Title')
                        ->maxLength(70)
                        ->placeholder(setting('site_name', config('app.name')).' — '.setting('site_tagline', 'Unggul, Berkarakter, Berprestasi'))
                        ->helperText('Judul di tab browser & hasil pencarian. Ideal 50–60 karakter. Kosongkan untuk memakai Nama Sekolah + Tagline.')
                        ->columnSpanFull(),

                    Textarea::make('home_meta_description')
                        ->label('Meta Description')
                        ->rows(3)
                        ->maxLength(160)
                        ->helperText('Ringkasan yang tampil di hasil pencarian Google. Ideal 150–160 karakter. Kosongkan untuk memakai Deskripsi Singkat dari Pengaturan Umum.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * Judul kartu repeater: nama seksi, ID anchor-nya, ditambah penanda bila
     * seksi itu disembunyikan.
     *
     * @param  array<string, mixed>  $state
     */
    private static function itemLabel(array $state): ?string
    {
        $label = $state['label'] ?? null;

        if ($label === null) {
            return null;
        }

        if (filled($state['anchor'] ?? null)) {
            $label .= '   ·   #'.$state['anchor'];
        }

        return ($state['visible'] ?? true) ? $label : $label.'   ·   disembunyikan';
    }

    /**
     * Isi kartu: saklar tampil, lalu kolom teks milik seksi itu. Kolom teks
     * memakai satu set nama yang sama untuk semua seksi — label, placeholder,
     * dan visibilitasnya yang menyesuaikan kunci seksi pada baris tersebut.
     *
     * @return array<int, mixed>
     */
    private function sectionItemSchema(): array
    {
        $isEditable = fn (Get $get): bool => self::contentConfig($get('key')) !== null;

        return [
            Hidden::make('key'),
            Hidden::make('label'),
            Hidden::make('anchor'),

            Placeholder::make('anchor_hint')
                ->label('ID Seksi (Anchor)')
                ->content(fn (Get $get): HtmlString => self::anchorHint($get('key'), $get('anchor')))
                ->columnSpanFull(),

            Toggle::make('visible')
                ->label('Tampilkan seksi ini di halaman depan')
                ->onColor('success')
                ->offColor('danger')
                ->live()
                ->helperText(fn (Get $get): ?string => self::isDynamic($get('key'))
                    ? 'Saklar ini sekaligus mengubah status publikasi seksi dinamisnya.'
                    : null)
                ->columnSpanFull(),

            Placeholder::make('dynamic_link')
                ->label('Konten Seksi')
                ->visible(fn (Get $get): bool => self::isDynamic($get('key')))
                ->content(fn (Get $get): HtmlString => self::dynamicSectionLink($get('key')))
                ->columnSpanFull(),

            Placeholder::make('managed_elsewhere')
                ->label('Konten Seksi')
                ->visible(fn (Get $get): bool => ! self::isDynamic($get('key')) && ! $isEditable($get) && $get('key') !== self::HERO_KEY)
                ->content('Seksi ini tidak punya teks judul yang bisa diubah di sini — isinya diambil dari menu datanya masing-masing.')
                ->columnSpanFull(),

            Grid::make(2)
                ->visible($isEditable)
                ->schema([
                    TextInput::make('eyebrow')
                        ->label('Label Kecil')
                        ->maxLength(60)
                        ->placeholder(fn (Get $get): ?string => self::contentConfig($get('key'))['eyebrow'] ?? null)
                        ->helperText('Teks kecil di atas judul. Kosongkan untuk menyembunyikan.'),

                    TextInput::make('title')
                        ->label('Judul')
                        ->maxLength(120)
                        ->placeholder(fn (Get $get): ?string => self::contentConfig($get('key'))['title'] ?? null),

                    TextInput::make('title_highlight')
                        ->label('Judul — Bagian Disorot')
                        ->maxLength(120)
                        ->visible(fn (Get $get): bool => isset(self::contentConfig($get('key'))['highlight']))
                        ->placeholder(fn (Get $get): ?string => self::contentConfig($get('key'))['highlight'] ?? null)
                        ->helperText('Ditampilkan di baris kedua judul dengan warna aksen.')
                        ->columnSpanFull(),

                    Textarea::make('subtitle')
                        ->label('Deskripsi')
                        ->rows(2)
                        ->maxLength(300)
                        ->placeholder(fn (Get $get): ?string => self::contentConfig($get('key'))['subtitle'] ?? null)
                        ->helperText('Kosongkan untuk menyembunyikan deskripsi.')
                        ->columnSpanFull(),

                    ...$this->extraTextFields(),
                ]),

            ...$this->heroSliderFields(),

            ...$this->alumniMarqueeFields(),

            ...$this->backgroundFields(),
        ];
    }

    /**
     * Perilaku & ukuran baris logo kampus pada seksi Jejak Alumni. Isinya
     * dibaca `AlumniMarquee`.
     *
     * @return array<int, mixed>
     */
    private function alumniMarqueeFields(): array
    {
        $isRolling = fn (Get $get): bool => (bool) $get('alumni_autoplay');

        return [
            Section::make('Baris Logo Kampus')
                ->description('Logo kampusnya sendiri diatur di menu Kampus Alumni. Di sini cara barisnya berjalan dan ukuran kartunya.')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->visible(fn (Get $get): bool => $get('key') === self::ALUMNI_KEY)
                ->collapsible()
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    Toggle::make('alumni_autoplay')
                        ->label('Jalan Otomatis')
                        ->default(true)
                        ->onColor('success')
                        ->live()
                        ->helperText('Matikan agar barisnya diam dan hanya bergerak saat pengunjung menggeser atau mengusapnya.')
                        ->columnSpanFull(),

                    Toggle::make('alumni_pause_on_hover')
                        ->label('Berhenti Saat Kursor di Atas Baris')
                        ->default(true)
                        ->onColor('success')
                        ->visible($isRolling)
                        ->helperText('Nyalakan agar baris berhenti selama kursor berada di atasnya, matikan agar tetap berjalan terus.')
                        ->columnSpanFull(),

                    ToggleButtons::make('alumni_direction')
                        ->label('Arah Jalan')
                        ->options(AlumniMarquee::DIRECTIONS)
                        ->icons([
                            'left' => Heroicon::OutlinedArrowLeft,
                            'right' => Heroicon::OutlinedArrowRight,
                        ])
                        ->default('left')
                        ->required()
                        ->inline()
                        ->visible($isRolling)
                        ->columnSpanFull(),

                    Slider::make('alumni_speed')
                        ->label('Kecepatan (piksel per detik)')
                        ->range(minValue: AlumniMarquee::MIN_SPEED, maxValue: AlumniMarquee::MAX_SPEED)
                        ->step(1)
                        ->tooltips()
                        ->default(AlumniMarquee::DEFAULT_SPEED)
                        ->required()
                        ->visible($isRolling)
                        ->helperText('18 px/detik terasa tenang; makin besar makin cepat barisnya melaju.')
                        ->columnSpanFull(),

                    Slider::make('alumni_logo_height')
                        ->label('Tinggi Logo (piksel)')
                        ->range(minValue: AlumniMarquee::MIN_LOGO_HEIGHT, maxValue: AlumniMarquee::MAX_LOGO_HEIGHT)
                        ->step(2)
                        ->tooltips()
                        ->default(AlumniMarquee::DEFAULT_LOGO_HEIGHT)
                        ->required()
                        ->helperText('Lebar logo menyesuaikan sendiri mengikuti tingginya.')
                        ->columnSpanFull(),

                    Slider::make('alumni_card_width')
                        ->label('Lebar Kartu (piksel)')
                        ->range(minValue: AlumniMarquee::MIN_CARD_WIDTH, maxValue: AlumniMarquee::MAX_CARD_WIDTH)
                        ->step(4)
                        ->tooltips()
                        ->default(AlumniMarquee::DEFAULT_CARD_WIDTH)
                        ->required()
                        ->helperText('Kotak putih tempat logo berdiri. Perbesar bila nama kampusnya panjang.')
                        ->columnSpanFull(),

                    Slider::make('alumni_gap')
                        ->label('Jarak Antar Kartu (piksel)')
                        ->range(minValue: AlumniMarquee::MIN_GAP, maxValue: AlumniMarquee::MAX_GAP)
                        ->step(2)
                        ->tooltips()
                        ->default(AlumniMarquee::DEFAULT_GAP)
                        ->required()
                        ->columnSpanFull(),

                    Toggle::make('alumni_grayscale')
                        ->label('Logo Abu-abu Sampai Disentuh Kursor')
                        ->default(true)
                        ->onColor('success')
                        ->helperText('Matikan agar semua logo langsung tampil berwarna penuh.')
                        ->columnSpanFull(),

                    Toggle::make('alumni_show_name')
                        ->label('Tampilkan Nama Kampus di Bawah Logo')
                        ->default(true)
                        ->onColor('success')
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Perilaku slider hero: putar otomatis, jeda saat kursor menyentuh hero,
     * serta gaya & lama animasi perpindahannya. Isinya dibaca `HeroSlider`.
     *
     * @return array<int, mixed>
     */
    private function heroSliderFields(): array
    {
        return [
            Section::make('Perilaku Slider Hero')
                ->description('Gambar & isi tiap slide diatur di menu Slide. Di sini hanya cara slide berpindah.')
                ->icon(Heroicon::OutlinedPlayCircle)
                ->visible(fn (Get $get): bool => $get('key') === self::HERO_KEY)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('hero_autoplay')
                        ->label('Putar Otomatis')
                        ->default(true)
                        ->onColor('success')
                        ->live()
                        ->helperText('Matikan agar slide hanya berpindah saat pengunjung menekan panah atau titik indikator.')
                        ->columnSpanFull(),

                    Toggle::make('hero_pause_on_hover')
                        ->label('Jeda Saat Kursor di Atas Hero')
                        ->default(true)
                        ->onColor('success')
                        ->visible(fn (Get $get): bool => (bool) $get('hero_autoplay'))
                        ->helperText('Slide berhenti berpindah selama kursor berada di area hero, dan lanjut lagi setelah kursor pergi.')
                        ->columnSpanFull(),

                    Slider::make('hero_interval')
                        ->label('Jeda Antar Slide (detik)')
                        ->range(minValue: HeroSlider::MIN_INTERVAL, maxValue: HeroSlider::MAX_INTERVAL)
                        ->step(1)
                        ->tooltips()
                        ->default(HeroSlider::DEFAULT_INTERVAL)
                        ->required()
                        ->visible(fn (Get $get): bool => (bool) $get('hero_autoplay'))
                        ->helperText('Lama satu slide ditampilkan sebelum berganti ke slide berikutnya.')
                        ->columnSpanFull(),

                    ToggleButtons::make('hero_transition')
                        ->label('Gaya Perpindahan')
                        ->options(HeroSlider::TRANSITIONS)
                        ->icons([
                            'fade' => Heroicon::OutlinedSparkles,
                            'slide' => Heroicon::OutlinedArrowsRightLeft,
                            'slide-vertical' => Heroicon::OutlinedArrowsUpDown,
                            'zoom' => Heroicon::OutlinedMagnifyingGlassPlus,
                        ])
                        ->default('fade')
                        ->required()
                        ->inline()
                        ->helperText('Memudar: slide bertukar halus di tempat. Geser: slide masuk dari sisi sesuai arah tombol. Zoom: slide masuk sambil mengecil ke ukuran normal.')
                        ->columnSpanFull(),

                    Slider::make('hero_transition_duration')
                        ->label('Lama Animasi Perpindahan (milidetik)')
                        ->range(minValue: HeroSlider::MIN_DURATION, maxValue: HeroSlider::MAX_DURATION)
                        ->step(50)
                        ->tooltips()
                        ->default(HeroSlider::DEFAULT_DURATION)
                        ->required()
                        ->helperText('700 ms terasa halus; makin kecil makin cepat dan tegas.')
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Pengaturan latar seksi bawaan — sama persis dengan yang dimiliki seksi
     * dinamis, hanya nilainya disimpan sebagai setting.
     *
     * @return array<int, mixed>
     */
    private function backgroundFields(): array
    {
        $usesImage = fn (Get $get): bool => $get('background') === 'image';

        return [
            Section::make('Latar Belakang Seksi')
                ->description('Biarkan "Bawaan Seksi" untuk memakai latar rancangan aslinya, atau ganti dengan warna polos maupun gambar penuh berikut efeknya. Latar non-gambar bisa dihias pola SVG, dengan atau tanpa animasi.')
                ->icon(Heroicon::OutlinedSwatch)
                ->visible(fn (Get $get): bool => self::supportsBackground($get('key')))
                ->collapsible()
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    ToggleButtons::make('background')
                        ->label('Jenis Latar')
                        ->options(SectionBackground::BACKGROUNDS)
                        ->icons([
                            'default' => Heroicon::OutlinedSparkles,
                            'base' => Heroicon::OutlinedSquares2x2,
                            'alt' => Heroicon::OutlinedStop,
                            'image' => Heroicon::OutlinedPhoto,
                        ])
                        ->default('default')
                        ->required()
                        ->inline()
                        ->live()
                        ->helperText('Selang-seling abu lembut dan putih bersih agar antar seksi tidak menyatu.')
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
                                ->helperText('Hiasan SVG halus di atas latar seksi, diwarnai mengikuti warna utama tema.'),

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
                        directory: 'sections/backgrounds',
                        aspectRatio: '16:9',
                    )
                        ->visible($usesImage)
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->visible($usesImage)
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
                                ->range(minValue: ContentSection::PARALLAX_MIN_SPEED, maxValue: ContentSection::PARALLAX_MAX_SPEED)
                                ->step(1)
                                ->tooltips()
                                ->default(30)
                                ->required()
                                ->visible(fn (Get $get): bool => $get('background_parallax_mode') === 'scroll')
                                ->helperText('1 = nyaris diam, 100 = geser paling jauh. Makin tinggi, gambar latar makin diperbesar agar tepinya tidak menyingkap — pakai gambar resolusi tinggi untuk nilai besar.')
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    /**
     * Kolom teks tambahan milik seksi tertentu, misal judul baris logo kampus
     * pada seksi alumni.
     *
     * @return array<int, TextInput>
     */
    private function extraTextFields(): array
    {
        $fields = [];

        foreach (self::extraFields() as $suffix => $extra) {
            $fields[] = TextInput::make("extra_{$suffix}")
                ->label($extra['label'])
                ->maxLength(120)
                ->visible(fn (Get $get): bool => $get('key') === $extra['owner'])
                ->placeholder($extra['default'])
                ->helperText($extra['helperText'] ?? null)
                ->columnSpanFull();
        }

        return $fields;
    }

    /**
     * Tautan ke halaman edit seksi dinamis, tempat gambar & tombolnya diubah.
     */
    private static function dynamicSectionLink(?string $key): HtmlString
    {
        $id = ContentSection::idFromOrderKey((string) $key);

        if ($id === null || ! ContentSection::whereKey($id)->exists()) {
            return new HtmlString('Seksi ini sudah dihapus.');
        }

        $url = ContentSectionResource::getUrl('edit', ['record' => $id]);

        return new HtmlString(
            '<a href="'.e($url).'" class="fi-link fi-size-sm" style="color:var(--primary-600,#2563eb);font-weight:600;text-decoration:underline;">'
            .'Ubah gambar, judul, deskripsi &amp; tombol seksi ini →</a>'
        );
    }

    /**
     * ID anchor seksi beserta contoh pemakaiannya sebagai tujuan tautan menu.
     * Seksi bawaan memakai id tetap dari partial Blade-nya; seksi dinamis
     * memakai kolom "ID Anchor" pada record-nya.
     */
    private static function anchorHint(?string $key, ?string $anchor): HtmlString
    {
        if (blank($anchor)) {
            return new HtmlString('<span style="opacity:.7">Seksi ini belum punya ID anchor.</span>');
        }

        // Latar & garis chip diracik dari warna teks yang berlaku, sehingga
        // tetap terbaca baik di tema terang maupun gelap.
        $chip = '<code style="background:color-mix(in oklab, currentColor 12%, transparent);'
            .'border:1px solid color-mix(in oklab, currentColor 20%, transparent);'
            .'border-radius:.375rem;padding:.125rem .375rem;font-weight:600">#'.e($anchor).'</code>';

        $note = self::isDynamic($key)
            ? 'Ubah lewat kolom "ID Anchor" pada seksi dinamisnya.'
            : 'Dari halaman lain, tulis <code>/#'.e($anchor).'</code>.';

        return new HtmlString(
            $chip.'<div style="margin-top:.35rem;font-size:.8125rem;opacity:.75">'
            .'Pakai sebagai URL menu atau tautan cepat untuk meluncur ke seksi ini. '.$note
            .'</div>'
        );
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $sections = $data['sections'] ?? [];

        $payload = [
            'home_meta_title' => $data['home_meta_title'] ?? '',
            'home_meta_description' => $data['home_meta_description'] ?? '',
        ];

        $order = [];

        // array_values preserves drag-and-drop order from Repeater
        foreach (array_values($sections) as $section) {
            $key = $section['key'] ?? null;

            if (! $key) {
                continue;
            }

            $order[] = [
                'key' => $key,
                'visible' => (bool) ($section['visible'] ?? true),
            ];

            $payload = [
                ...$payload,
                ...self::contentPayload($key, $section),
                ...self::backgroundPayload($key, $section),
                ...self::heroSliderPayload($key, $section),
                ...self::alumniMarqueePayload($key, $section),
            ];
        }

        $payload['section_order'] = json_encode($order);

        Setting::setMany($payload);

        $this->syncContentSectionVisibility($sections);

        Notification::make()
            ->success()
            ->title('Pengaturan halaman depan disimpan')
            ->send();
    }

    /**
     * Teks satu seksi, dipetakan balik ke kunci setting datar yang dibaca
     * partial Blade (`section_programs_title`, dan seterusnya).
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private static function contentPayload(string $key, array $section): array
    {
        $config = self::contentConfig($key);

        if ($config === null) {
            return [];
        }

        $payload = [
            "{$key}_eyebrow" => $section['eyebrow'] ?? '',
            "{$key}_title" => $section['title'] ?? '',
            "{$key}_subtitle" => $section['subtitle'] ?? '',
        ];

        if (isset($config['highlight'])) {
            $payload["{$key}_title_highlight"] = $section['title_highlight'] ?? '';
        }

        foreach (array_keys($config['extra'] ?? []) as $suffix) {
            $payload["{$key}_{$suffix}"] = $section["extra_{$suffix}"] ?? '';
        }

        return $payload;
    }

    /**
     * Perilaku slider hero, disimpan sebagai setting `hero_*` yang dibaca
     * `HeroSlider::fromSettings()`.
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private static function heroSliderPayload(string $key, array $section): array
    {
        if ($key !== self::HERO_KEY) {
            return [];
        }

        return [
            'hero_autoplay' => (bool) ($section['hero_autoplay'] ?? true),
            'hero_pause_on_hover' => (bool) ($section['hero_pause_on_hover'] ?? true),
            'hero_interval' => (int) ($section['hero_interval'] ?? HeroSlider::DEFAULT_INTERVAL),
            'hero_transition' => $section['hero_transition'] ?? 'fade',
            'hero_transition_duration' => (int) ($section['hero_transition_duration'] ?? HeroSlider::DEFAULT_DURATION),
        ];
    }

    /**
     * Perilaku & ukuran baris logo kampus, disimpan sebagai setting
     * `alumni_marquee_*` yang dibaca `AlumniMarquee::fromSettings()`.
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private static function alumniMarqueePayload(string $key, array $section): array
    {
        if ($key !== self::ALUMNI_KEY) {
            return [];
        }

        return [
            'alumni_marquee_autoplay' => (bool) ($section['alumni_autoplay'] ?? true),
            'alumni_marquee_pause_on_hover' => (bool) ($section['alumni_pause_on_hover'] ?? true),
            'alumni_marquee_speed' => (int) ($section['alumni_speed'] ?? AlumniMarquee::DEFAULT_SPEED),
            'alumni_marquee_direction' => $section['alumni_direction'] ?? 'left',
            'alumni_marquee_logo_height' => (int) ($section['alumni_logo_height'] ?? AlumniMarquee::DEFAULT_LOGO_HEIGHT),
            'alumni_marquee_card_width' => (int) ($section['alumni_card_width'] ?? AlumniMarquee::DEFAULT_CARD_WIDTH),
            'alumni_marquee_gap' => (int) ($section['alumni_gap'] ?? AlumniMarquee::DEFAULT_GAP),
            'alumni_marquee_grayscale' => (bool) ($section['alumni_grayscale'] ?? true),
            'alumni_marquee_show_name' => (bool) ($section['alumni_show_name'] ?? true),
        ];
    }

    /**
     * Latar satu seksi bawaan, dipetakan ke kunci setting datar yang dibaca
     * komponen `<x-section-background>` (`section_stats_background`, dst).
     * Gambar yang dipilih dari media library atau baru diunggah diselesaikan
     * dulu lewat pemilih gambar bersama.
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private static function backgroundPayload(string $key, array $section): array
    {
        if (! self::supportsBackground($key)) {
            return [];
        }

        $section = self::applyImagePickers($section, ['background_image']);

        return [
            "{$key}_background" => $section['background'] ?? 'default',
            "{$key}_background_image" => $section['background_image'] ?? '',
            "{$key}_background_blur" => (int) ($section['background_blur'] ?? 0),
            "{$key}_background_overlay" => (int) ($section['background_overlay'] ?? 0),
            "{$key}_background_light_text" => (bool) ($section['background_light_text'] ?? true),
            "{$key}_background_parallax_mode" => $section['background_parallax_mode'] ?? 'none',
            "{$key}_background_parallax_speed" => (int) ($section['background_parallax_speed'] ?? 30),
            "{$key}_background_pattern" => $section['background_pattern'] ?? 'none',
            "{$key}_background_pattern_opacity" => (int) ($section['background_pattern_opacity'] ?? ContentSection::DEFAULT_PATTERN_OPACITY),
            "{$key}_background_pattern_scale" => (int) ($section['background_pattern_scale'] ?? ContentSection::DEFAULT_PATTERN_SCALE),
            "{$key}_background_pattern_color" => $section['background_pattern_color'] ?? ContentSection::DEFAULT_PATTERN_COLOR,
            "{$key}_background_pattern_custom_color" => $section['background_pattern_custom_color'] ?? '',
            "{$key}_background_pattern_animated" => (bool) ($section['background_pattern_animated'] ?? false),
            "{$key}_background_pattern_motion" => $section['background_pattern_motion'] ?? ContentSection::DEFAULT_PATTERN_MOTION,
            "{$key}_background_pattern_speed" => (int) ($section['background_pattern_speed'] ?? ContentSection::DEFAULT_PATTERN_SPEED),
        ];
    }

    /**
     * Toggle seksi dinamis di halaman ini adalah jalan pintas ke status publikasi
     * record-nya, agar tidak ada dua sumber kebenaran yang bisa berbeda.
     *
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function syncContentSectionVisibility(array $sections): void
    {
        foreach ($sections as $section) {
            $id = ContentSection::idFromOrderKey((string) ($section['key'] ?? ''));

            if ($id === null) {
                continue;
            }

            ContentSection::whereKey($id)->update([
                'is_published' => (bool) ($section['visible'] ?? true),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),

            Action::make('newContentSection')
                ->label('Seksi Baru')
                ->icon(Heroicon::OutlinedPlus)
                ->color('gray')
                ->url(ContentSectionResource::getUrl('create')),
        ];
    }
}
