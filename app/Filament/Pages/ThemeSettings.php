<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\ContentTypography;
use App\Support\PageGutter;
use App\Support\PageWidth;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ThemeSettings extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.general-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Tema & Tampilan';

    protected static ?string $title = 'Pengaturan Tema & Tampilan';

    protected static ?int $navigationSort = 6;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $savedColor = Setting::get('theme_primary_color', '#d97706');
        $savedFont = Setting::get('theme_font', 'instrument-sans');

        $this->form->fill([
            'theme_preset' => $this->matchPreset($savedColor),
            'theme_primary_color' => $savedColor,
            'theme_font' => $savedFont,
            'theme_font_custom_url' => Setting::get('theme_font_custom_url', ''),
            'theme_font_custom_family' => Setting::get('theme_font_custom_family', ''),
            'content_font_size' => ContentTypography::size(),
            PageWidth::SETTING_KEY => PageWidth::key(),
            ...self::gutterFormState(PageGutter::values()),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Warna Utama Website')
                ->description('Warna ini diterapkan ke seluruh elemen utama website: tombol, label, highlight, dan aksen navigasi.')
                ->icon(Heroicon::OutlinedSwatch)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('theme_preset')
                            ->label('Preset Warna')
                            ->options(self::presets())
                            ->searchable(false)
                            ->placeholder('— Pilih preset —')
                            ->live()
                            ->afterStateUpdated(
                                fn (Set $set, ?string $state) => $state
                                    ? $set('theme_primary_color', $state)
                                    : null
                            )
                            ->hint('Pilih preset atau ubah warna secara kustom di sebelah kanan.'),

                        ColorPicker::make('theme_primary_color')
                            ->label('Warna Kustom (HEX)')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn (Set $set, ?string $state) => $set(
                                    'theme_preset',
                                    $this->matchPreset($state ?? '#d97706')
                                )
                            )
                            ->hint('Klik kotak warna untuk membuka color picker.'),
                    ]),
                ]),

            Section::make('Tipografi')
                ->description('Pilih font yang digunakan di seluruh tampilan publik website.')
                ->icon(Heroicon::OutlinedDocumentText)
                ->schema([
                    Select::make('theme_font')
                        ->label('Font Website')
                        ->options(self::fonts())
                        ->allowHtml()
                        ->searchable()
                        ->live()
                        ->default('instrument-sans')
                        ->hint('Setiap opsi ditampilkan dengan font aslinya. Perubahan langsung terlihat setelah disimpan.'),

                    TextInput::make('theme_font_custom_url')
                        ->label('URL / Embed Google Fonts')
                        ->placeholder('<link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700"> — atau tempel URL-nya saja')
                        ->visible(fn (Get $get): bool => $get('theme_font') === 'custom')
                        ->live(debounce: 600)
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if ($family = google_font_family($state)) {
                                $set('theme_font_custom_family', $family);
                            }
                        })
                        ->helperText('Buka fonts.google.com → pilih font → "Get font" → "Get embed code" → salin baris <link> atau @import, lalu tempel di sini. Pratinjau muncul otomatis.'),

                    TextInput::make('theme_font_custom_family')
                        ->label('Nama Font (font-family)')
                        ->placeholder('mis. Roboto Slab')
                        ->visible(fn (Get $get): bool => $get('theme_font') === 'custom')
                        ->live(debounce: 600)
                        ->helperText('Terisi otomatis dari URL di atas — samakan persis dengan nama font di Google Fonts.'),

                    Placeholder::make('font_preview')
                        ->label('Pratinjau')
                        ->content(function (Get $get): HtmlString {
                            if (($get('theme_font') ?? 'instrument-sans') === 'custom') {
                                return self::customFontPreview($get('theme_font_custom_family'), $get('theme_font_custom_url'));
                            }

                            return self::fontPreview($get('theme_font') ?? 'instrument-sans');
                        }),
                ]),

            Section::make('Ukuran Teks Konten')
                ->description('Berlaku untuk isi artikel, halaman, program, agenda, kisah, dan blok "Konten Tambahan". Bagian lain website — judul seksi, kartu, dan menu — tidak terpengaruh.')
                ->icon(Heroicon::OutlinedBars3BottomLeft)
                ->schema([
                    Select::make('content_font_size')
                        ->label('Ukuran Teks Konten')
                        ->options(ContentTypography::SIZES)
                        ->selectablePlaceholder(false)
                        ->live()
                        ->default(ContentTypography::DEFAULT_SIZE)
                        ->helperText('Ukuran di layar lebar; di layar kecil ikut mengecil dengan proporsi yang sama. Judul, kutipan, dan tabel di dalam konten ikut menyesuaikan. Pilih 16px agar teks konten setara dengan teks umum website.'),

                    Placeholder::make('content_font_preview')
                        ->label('Pratinjau')
                        ->content(fn (Get $get): HtmlString => self::contentSizePreview(
                            (int) ($get('content_font_size') ?? ContentTypography::DEFAULT_SIZE)
                        )),
                ]),

            Section::make('Lebar & Margin Halaman')
                ->description('Mengatur seberapa lebar isi website dan berapa jaraknya dari tepi layar. Berlaku untuk seluruh halaman publik — beranda, artikel, program, dan lainnya — termasuk menu atas dan footer.')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->schema([
                    Select::make(PageWidth::SETTING_KEY)
                        ->label('Lebar Maksimum Isi Halaman')
                        ->options(PageWidth::options())
                        ->selectablePlaceholder(false)
                        ->live()
                        ->default(PageWidth::DEFAULT_WIDTH)
                        ->helperText('Batas lebar isi website di layar besar; isinya tetap dipusatkan. "Penuh Layar" membuat isi melebar mengikuti layar, sehingga yang menyisakan ruang tepi hanya margin di bawah ini.'),

                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 5])->schema(
                        collect(PageGutter::BREAKPOINTS)
                            ->map(fn (array $breakpoint, string $key): TextInput => TextInput::make(PageGutter::settingKey($key))
                                ->label($breakpoint['label'])
                                ->numeric()
                                ->live(onBlur: true)
                                ->minValue(PageGutter::MIN)
                                ->maxValue(PageGutter::MAX)
                                ->step(1)
                                ->suffix('px')
                                ->required()
                                ->default($breakpoint['default'])
                                ->helperText($breakpoint['hint'].' — bawaan '.$breakpoint['default'].'px')
                                ->columnSpan(1))
                            ->values()
                            ->all()
                    ),

                    Placeholder::make('page_layout_preview')
                        ->label('Pratinjau')
                        ->content(fn (Get $get): HtmlString => self::layoutPreview($get)),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('theme_primary_color', $data['theme_primary_color'] ?? '#d97706');
        Setting::set('theme_font', $data['theme_font'] ?? 'instrument-sans');
        Setting::set('theme_font_custom_url', google_font_url($data['theme_font_custom_url'] ?? '') ?? '');
        Setting::set('theme_font_custom_family', clean_font_family_name($data['theme_font_custom_family'] ?? ''));
        Setting::set(ContentTypography::SETTING_KEY, ContentTypography::sanitizeSize($data['content_font_size'] ?? null));

        Setting::set(PageWidth::SETTING_KEY, PageWidth::sanitize($data[PageWidth::SETTING_KEY] ?? null));

        foreach (array_keys(PageGutter::BREAKPOINTS) as $breakpoint) {
            $key = PageGutter::settingKey($breakpoint);

            Setting::set($key, PageGutter::sanitize($data[$key] ?? null, $breakpoint));
        }

        Notification::make()
            ->success()
            ->title('Tema berhasil disimpan')
            ->body('Perubahan warna, font, ukuran teks konten, serta lebar dan margin halaman akan langsung terlihat di website.')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Tema')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),

            Action::make('reset')
                ->label('Reset ke Default')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Reset Tema?')
                ->modalDescription('Warna akan dikembalikan ke Amber, font ke Instrument Sans, ukuran teks konten ke '.ContentTypography::DEFAULT_SIZE.'px, serta lebar dan margin halaman ke ukuran bawaan.')
                ->action(function (): void {
                    Setting::set('theme_primary_color', '#d97706');
                    Setting::set('theme_font', 'instrument-sans');
                    Setting::set('theme_font_custom_url', '');
                    Setting::set('theme_font_custom_family', '');
                    Setting::set(ContentTypography::SETTING_KEY, ContentTypography::DEFAULT_SIZE);

                    Setting::set(PageWidth::SETTING_KEY, PageWidth::DEFAULT_WIDTH);

                    foreach (PageGutter::settingDefaults() as $key => $value) {
                        Setting::set($key, $value);
                    }

                    $this->form->fill([
                        'theme_preset' => '#d97706',
                        'theme_primary_color' => '#d97706',
                        'theme_font' => 'instrument-sans',
                        'theme_font_custom_url' => '',
                        'theme_font_custom_family' => '',
                        'content_font_size' => ContentTypography::DEFAULT_SIZE,
                        PageWidth::SETTING_KEY => PageWidth::DEFAULT_WIDTH,
                        ...self::gutterFormState(PageGutter::defaults()),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Tema direset ke default (Amber + Instrument Sans)')
                        ->send();
                }),
        ];
    }

    /**
     * Returns preset color options.
     *
     * @return array<string, string>
     */
    public static function presets(): array
    {
        return [
            '#d97706' => '🟠 Amber (Default)',
            '#f59e0b' => '🟡 Kuning',
            '#2563eb' => '🔵 Biru',
            '#4f46e5' => '🟣 Indigo',
            '#9333ea' => '🟣 Ungu',
            '#e11d48' => '🌸 Rose',
            '#dc2626' => '🔴 Merah',
            '#16a34a' => '🟢 Hijau',
            '#0d9488' => '🩵 Teal',
            '#0891b2' => '🔵 Cyan',
            '#ea580c' => '🟠 Oranye',
        ];
    }

    /**
     * Single source of truth for the available fonts. Pulled from config/fonts.php
     * and shared with the public layouts.
     *
     * @return array<string, array{label: string, family: string, google: string, group: string, bundled?: bool}>
     */
    public static function fontFamilies(): array
    {
        return config('fonts');
    }

    /**
     * Returns available font options grouped by category, with each label
     * rendered in its own font. Requires `allowHtml()` + `searchable()`.
     *
     * @return array<string, array<string, string>>
     */
    public static function fonts(): array
    {
        $options = [];

        foreach (self::fontFamilies() as $key => $font) {
            $options[$font['group']][$key] = '<span style="font-family: '.e($font['family']).'; font-size: 0.95rem;">'.e($font['label']).'</span>';
        }

        $options['Kustom']['custom'] = '<span style="font-size: 0.95rem;">✎ Font Kustom — tempel dari Google Fonts</span>';

        return $options;
    }

    /**
     * Builds the live preview markup for the given predefined font key.
     */
    public static function fontPreview(string $key): HtmlString
    {
        $family = self::fontFamilies()[$key]['family'] ?? self::fontFamilies()['instrument-sans']['family'];

        return self::buildPreview($family);
    }

    /**
     * Builds the live preview markup for a custom, admin-provided font. The
     * webfont stylesheet is embedded directly in the preview so it loads
     * server-side as soon as the URL is pasted — no extra client script needed.
     */
    public static function customFontPreview(?string $name, ?string $url): HtmlString
    {
        $name = clean_font_family_name($name);
        $family = $name !== ''
            ? '"'.$name.'", ui-sans-serif, system-ui, sans-serif'
            : 'ui-sans-serif, system-ui, sans-serif';

        $href = google_font_url($url);
        $stylesheet = $href ? '<link rel="stylesheet" href="'.e($href).'">' : '';

        return self::buildPreview($family, $stylesheet);
    }

    /**
     * Renders a sample content block at the chosen size, with a 16px reference
     * line below it so the admin can judge it against the rest of the website.
     */
    protected static function contentSizePreview(int $size): HtmlString
    {
        $size = ContentTypography::sanitizeSize($size);
        $heading = round(1.65 * 16 * ContentTypography::scale($size));

        return new HtmlString(<<<HTML
            <div style="border: 1px solid rgba(0,0,0,.1); border-radius: .75rem; padding: 1rem 1.25rem; background: #fff;">
                <div style="font-size: {$heading}px; font-weight: 800; line-height: 1.3; color: #111827;">Judul di Dalam Konten</div>
                <p style="font-size: {$size}px; line-height: 1.85; margin-top: .625rem; color: #374151;">Isi artikel, halaman, dan program akan tampil seukuran ini. Aturlah sampai enak dibaca tanpa terasa lebih besar daripada bagian website lainnya.</p>
                <p style="font-size: 16px; line-height: 1.6; margin-top: .875rem; padding-top: .625rem; border-top: 1px dashed rgba(0,0,0,.12); color: #6e6e73;">Pembanding — teks umum website berukuran 16px.</p>
            </div>
        HTML);
    }

    /**
     * Maps breakpoint-keyed gutter values onto their form field names.
     *
     * @param  array<string, int>  $values
     * @return array<string, int>
     */
    protected static function gutterFormState(array $values): array
    {
        $state = [];

        foreach ($values as $breakpoint => $value) {
            $state[PageGutter::settingKey($breakpoint)] = $value;
        }

        return $state;
    }

    /**
     * Renders a scaled mock-up per screen size: the shaded bands are the empty
     * space left by the margin plus the max-width cap, and the middle block is
     * the resulting content width — so the admin can judge both settings
     * together before saving.
     */
    protected static function layoutPreview(Get $get): HtmlString
    {
        $gutters = [];

        foreach (array_keys(PageGutter::BREAKPOINTS) as $breakpoint) {
            $gutters[$breakpoint] = PageGutter::sanitize($get(PageGutter::settingKey($breakpoint)), $breakpoint);
        }

        $maxWidth = PageWidth::pixels(PageWidth::sanitize($get(PageWidth::SETTING_KEY)));
        $rows = '';

        foreach (self::previewScreens($gutters) as $screen) {
            $available = max(0, $screen['width'] - (2 * $screen['gutter']));
            $content = $maxWidth === null ? $available : min($maxWidth, $available);
            $contentPercent = $screen['width'] > 0 ? round($content / $screen['width'] * 100, 2) : 0;
            $sidePercent = round((100 - $contentPercent) / 2, 2);
            $capped = $maxWidth !== null && $content < $available ? ' (dibatasi lebar maksimum)' : '';

            $rows .= <<<HTML
                <div style="display: flex; align-items: center; gap: .75rem; margin-top: .5rem;">
                    <div style="flex: 0 0 8.5rem; font-size: .75rem; color: #6e6e73;">{$screen['label']}<br><span style="color:#9ca3af;">layar {$screen['width']}px</span></div>
                    <div style="flex: 1; display: flex; align-items: stretch; height: 2.25rem; border: 1px solid rgba(0,0,0,.12); border-radius: .375rem; overflow: hidden; background: #fff;">
                        <div style="width: {$sidePercent}%; background: repeating-linear-gradient(45deg, rgba(0,0,0,.06) 0 4px, transparent 4px 8px);"></div>
                        <div style="width: {$contentPercent}%; display: flex; align-items: center; justify-content: center; background: #f5f5f7; font-size: .6875rem; color: #6e6e73;">konten</div>
                        <div style="width: {$sidePercent}%; background: repeating-linear-gradient(45deg, rgba(0,0,0,.06) 0 4px, transparent 4px 8px);"></div>
                    </div>
                    <div style="flex: 0 0 9rem; text-align: right; font-size: .75rem; font-weight: 600; color: #374151;">{$content}px<span style="display:block; font-weight:400; color:#9ca3af;">margin {$screen['gutter']}px{$capped}</span></div>
                </div>
            HTML;
        }

        return new HtmlString(<<<HTML
            <div style="border: 1px solid rgba(0,0,0,.1); border-radius: .75rem; padding: 1rem 1.25rem; background: #fff;">
                {$rows}
                <p style="font-size: .75rem; color: #6e6e73; margin-top: .875rem;">Bagian bergaris adalah ruang kosong di kiri &amp; kanan, blok tengah adalah lebar isi website. Gambar ini diskalakan, jadi bacalah angkanya — bukan ukuran di layar ini.</p>
            </div>
        HTML);
    }

    /**
     * Screen widths used by the preview: one typical width per breakpoint, plus
     * a wide monitor to show where the max-width cap starts to matter.
     *
     * @param  array<string, int>  $gutters
     * @return array<int, array{label: string, width: int, gutter: int}>
     */
    protected static function previewScreens(array $gutters): array
    {
        $screens = [];

        foreach (PageGutter::BREAKPOINTS as $breakpoint => $config) {
            $screens[] = [
                'label' => $config['label'],
                'width' => $config['reference_width'],
                'gutter' => $gutters[$breakpoint],
            ];
        }

        $screens[] = [
            'label' => 'Monitor Lebar',
            'width' => 1920,
            'gutter' => $gutters['xl'],
        ];

        return $screens;
    }

    /**
     * Renders the shared preview card for a resolved CSS font-family stack,
     * optionally preceded by a stylesheet that loads the webfont.
     */
    protected static function buildPreview(string $family, string $stylesheet = ''): HtmlString
    {
        $family = e($family);

        return new HtmlString(<<<HTML
            {$stylesheet}
            <div style="font-family: {$family}; border: 1px solid rgba(0,0,0,.1); border-radius: .75rem; padding: 1rem 1.25rem; background: #fff;">
                <div style="font-size: 1.5rem; font-weight: 700; line-height: 1.3; color: #1d1d1f;">Ar-Rahman Qur'an Academy</div>
                <div style="font-size: 1rem; font-weight: 500; margin-top: .25rem; color: #1d1d1f;">Menyemai generasi Qur'ani yang berakhlak.</div>
                <p style="font-size: .875rem; margin-top: .5rem; color: #6e6e73;">The quick brown fox jumps over the lazy dog — 0123456789</p>
            </div>
        HTML);
    }

    /**
     * Combined Google Fonts stylesheet URL that loads every selectable font so
     * the dropdown options and the live preview render in their real typeface.
     */
    public function googleFontsHref(): string
    {
        $families = collect(self::fontFamilies())
            ->pluck('google')
            ->filter()
            ->map(fn (string $google): string => 'family='.$google)
            ->implode('&');

        return 'https://fonts.googleapis.com/css2?'.$families.'&display=swap';
    }

    /**
     * Returns the preset key if the given color matches one, otherwise null.
     */
    private function matchPreset(string $color): ?string
    {
        $normalized = strtolower(trim($color));

        return array_key_exists($normalized, self::presets()) ? $normalized : null;
    }
}
