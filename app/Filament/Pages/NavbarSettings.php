<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\NavHighlight;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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

class NavbarSettings extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.general-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Menu Navigasi';

    protected static ?string $title = 'Pengaturan Menu Navigasi';

    protected static ?int $navigationSort = 2;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $saved = json_decode(Setting::get('nav_items', ''), true);

        $this->form->fill([
            'items' => is_array($saved) ? $saved : $this->defaultNavItems(),
            NavHighlight::SETTING_KEY => NavHighlight::color() ?? '',
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Item Menu')
                ->description('Seret untuk mengubah urutan. Setiap item dapat memiliki sub-menu (maks 1 level), lengkap dengan ikon dan keterangan singkat.')
                ->icon(Heroicon::OutlinedBars3)
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->schema([
                            Grid::make(4)->schema([
                                TextInput::make('label')
                                    ->label('Teks Menu')
                                    ->required()
                                    ->maxLength(60)
                                    ->placeholder('Beranda')
                                    ->columnSpan(2),

                                TextInput::make('url')
                                    ->label('URL / Tautan')
                                    ->required()
                                    ->maxLength(300)
                                    ->placeholder('/ atau #spmb atau /guru')
                                    ->columnSpan(1),

                                Select::make('target')
                                    ->label('Buka di')
                                    ->options(['_self' => 'Tab Sama', '_blank' => 'Tab Baru'])
                                    ->default('_self')
                                    ->columnSpan(1),
                            ]),

                            Toggle::make('is_active')
                                ->label('Tampilkan item ini')
                                ->default(true)
                                ->onColor('success')
                                ->inline(false),

                            Repeater::make('children')
                                ->label('Sub Menu')
                                ->schema([
                                    Grid::make(6)->schema([
                                        TextInput::make('icon')
                                            ->label('Ikon')
                                            ->maxLength(10)
                                            ->placeholder('📘')
                                            ->hint('Emoji')
                                            ->columnSpan(1),

                                        TextInput::make('label')
                                            ->label('Teks')
                                            ->required()
                                            ->maxLength(60)
                                            ->placeholder('Kurikulum')
                                            ->columnSpan(2),

                                        TextInput::make('url')
                                            ->label('URL')
                                            ->required()
                                            ->maxLength(300)
                                            ->placeholder('#kurikulum')
                                            ->columnSpan(2),

                                        Select::make('target')
                                            ->label('Buka di')
                                            ->options(['_self' => 'Tab Sama', '_blank' => 'Tab Baru'])
                                            ->default('_self')
                                            ->columnSpan(1),
                                    ]),

                                    TextInput::make('description')
                                        ->label('Keterangan')
                                        ->maxLength(80)
                                        ->placeholder('Struktur kurikulum & jadwal pelajaran')
                                        ->helperText('Teks kecil di bawah teks menu pada dropdown. Kosongkan bila tidak perlu.')
                                        ->columnSpanFull(),

                                    Toggle::make('is_active')
                                        ->label('Tampilkan')
                                        ->default(true)
                                        ->onColor('success')
                                        ->inline(false),
                                ])
                                ->reorderable()
                                ->collapsible()
                                ->collapsed()
                                ->defaultItems(0)
                                ->itemLabel(fn (array $state): string => $state['label'] ?? 'Item baru')
                                ->addActionLabel('+ Tambah Sub Menu')
                                ->columnSpanFull(),
                        ])
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): string => $state['label'] ?? 'Item baru')
                        ->addActionLabel('+ Tambah Item Menu')
                        ->columnSpanFull(),
                ]),

            Section::make('Warna Sorotan Menu')
                ->description('Warna yang dipakai menu saat disentuh kursor dan saat halaman — atau seksi — yang ditunjuknya sedang dibuka. Kosongkan agar ikut warna utama website di Tema & Tampilan.')
                ->icon(Heroicon::OutlinedSwatch)
                ->schema([
                    ColorPicker::make(NavHighlight::SETTING_KEY)
                        ->label('Warna Sorotan (HEX)')
                        ->live(onBlur: true)
                        ->rule('regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/')
                        ->validationMessages(['regex' => 'Isi dengan kode warna HEX, misalnya #08484a.'])
                        ->helperText('Kosongkan untuk mengikuti warna utama website. Di atas hero gelap dan di menu mobile, warna ini otomatis dicerahkan agar tetap terbaca.'),

                    Placeholder::make('nav_highlight_preview')
                        ->label('Pratinjau')
                        ->content(fn (Get $get): HtmlString => self::highlightPreview(
                            NavHighlight::sanitize($get(NavHighlight::SETTING_KEY))
                        )),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('nav_items', json_encode($data['items'] ?? []));
        Setting::set(NavHighlight::SETTING_KEY, NavHighlight::sanitize($data[NavHighlight::SETTING_KEY] ?? null));

        Notification::make()
            ->success()
            ->title('Menu navigasi berhasil disimpan')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Menu')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),

            Action::make('reset')
                ->label('Reset ke Default')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Reset Menu Navigasi?')
                ->modalDescription('Ini akan mengembalikan menu ke pengaturan awal bawaan dan warna sorotan ke warna utama website. Perubahan yang ada akan hilang.')
                ->action(function () {
                    Setting::set('nav_items', json_encode($this->defaultNavItems()));
                    Setting::set(NavHighlight::SETTING_KEY, NavHighlight::DEFAULT_COLOR);

                    $this->form->fill([
                        'items' => $this->defaultNavItems(),
                        NavHighlight::SETTING_KEY => NavHighlight::DEFAULT_COLOR,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Menu navigasi direset ke default')
                        ->send();
                }),
        ];
    }

    /**
     * Pratinjau sorotan di kedua keadaan yang ditemui pengunjung: bar putih di
     * halaman biasa, dan bar transparan di atas hero gelap beranda. Warna kosong
     * dipratinjaukan memakai warna utama website, persis seperti yang nanti
     * tampil.
     */
    protected static function highlightPreview(string $color): HtmlString
    {
        $base = e($color !== '' ? $color : Setting::get('theme_primary_color', '#d97706'));

        $soft = "color-mix(in oklab, {$base} 8%, white)";
        $bright = "color-mix(in oklab, {$base} 28%, white)";
        $veil = "color-mix(in oklab, {$bright} 16%, transparent)";

        $solid = self::previewBar(
            'background: #fff; border: 1px solid rgba(0,0,0,.08);',
            'color: #6b7280;',
            "color: {$base}; background: {$soft};",
            'Halaman biasa — bar putih'
        );

        $over = self::previewBar(
            'background: linear-gradient(145deg,#0f172a 0%,#1a2744 50%,#0f2236 100%);',
            'color: rgba(255,255,255,.8);',
            "color: {$bright}; background: {$veil};",
            'Beranda — bar transparan di atas hero gelap'
        );

        return new HtmlString(<<<HTML
            <div style="display: grid; gap: .75rem;">
                {$solid}
                {$over}
                <p style="font-size: .75rem; color: #6e6e73;">Menu yang disorot adalah menu yang sedang dibuka — tampilan yang sama juga muncul saat kursor melewati menu lain.</p>
            </div>
        HTML);
    }

    /**
     * Satu baris pratinjau: tiga menu contoh dengan yang tengah tersorot.
     */
    protected static function previewBar(string $bar, string $idle, string $active, string $caption): string
    {
        $chip = 'padding: .5rem .75rem; border-radius: .5rem; font-size: .875rem; font-weight: 500;';

        return <<<HTML
            <div>
                <div style="{$bar} border-radius: .75rem; padding: .5rem .75rem; display: flex; align-items: center; gap: .25rem;">
                    <span style="{$chip} {$idle}">Beranda</span>
                    <span style="{$chip} {$active} font-weight: 600; box-shadow: inset 0 -2px 0 currentColor;">Profil</span>
                    <span style="{$chip} {$idle}">Kontak</span>
                </div>
                <p style="font-size: .6875rem; color: #9ca3af; margin-top: .25rem;">{$caption}</p>
            </div>
        HTML;
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultNavItems(): array
    {
        return [
            ['label' => 'Beranda',  'url' => '/',          'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'Profil',   'url' => '#profil',    'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'SPMB',     'url' => '#spmb',      'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'Akademik', 'url' => '#akademik',  'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'Guru',     'url' => '/guru',      'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'Blog',     'url' => '/blog',      'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'Kontak',   'url' => '#kontak',    'target' => '_self', 'is_active' => true, 'children' => []],
        ];
    }
}
