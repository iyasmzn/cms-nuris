<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ContentSections\ContentSectionResource;
use App\Models\ContentSection;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

    protected string $view = 'filament.pages.general-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Halaman Depan';

    protected static ?string $title = 'Pengaturan Halaman Depan';

    protected static ?int $navigationSort = 3;

    /**
     * Awalan kunci seksi bawaan di dalam `section_order`.
     */
    private const BUILT_IN_PREFIX = 'section_';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /**
     * Seksi bawaan ditambah seksi dinamis (ContentSection) yang dibuat admin,
     * sehingga keduanya bisa diurutkan dalam satu daftar.
     *
     * @return array<int, array{key: string, label: string, visible: bool}>
     */
    private static function defaultSections(): array
    {
        $sections = self::builtInSections();

        foreach (ContentSection::ordered()->get() as $contentSection) {
            $sections[] = [
                'key' => $contentSection->order_key,
                'label' => '🧩  '.$contentSection->title,
                'visible' => $contentSection->is_published,
            ];
        }

        return $sections;
    }

    /** @return array<int, array{key: string, label: string, visible: bool}> */
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
     * @param  array{key: string, label: string, visible: bool}  $section
     * @return array<string, mixed>
     */
    private function sectionItem(array $section, ?bool $visible = null): array
    {
        $item = [
            'key' => $section['key'],
            'label' => $section['label'],
            'visible' => $visible ?? $section['visible'],
        ];

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
     * Judul kartu repeater: nama seksi, ditambah penanda bila disembunyikan.
     *
     * @param  array<string, mixed>  $state
     */
    private static function itemLabel(array $state): ?string
    {
        $label = $state['label'] ?? null;

        if ($label === null) {
            return null;
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
                ->visible(fn (Get $get): bool => ! self::isDynamic($get('key')) && ! $isEditable($get))
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

            $payload = [...$payload, ...self::contentPayload($key, $section)];
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
