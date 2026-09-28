<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithPageHero;
use App\Filament\Schemas\PageHeroFields;
use App\Filament\Schemas\PpdbLandingBlocks;
use App\Models\Setting;
use App\Support\PpdbLanding;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Penyusun halaman depan PPDB (/ppdb): hero, lalu deretan seksi yang bisa
 * ditambah, dihapus, dan diurutkan — campuran seksi data PPDB dan blok bebas.
 */
class PpdbLandingSettings extends Page
{
    use HasPageShield;
    use InteractsWithPageHero;

    protected string $view = 'filament.pages.general-settings';

    protected static string|UnitEnum|null $navigationGroup = 'PPDB / SPMB';

    protected static ?string $navigationLabel = 'Halaman Depan PPDB';

    protected static ?string $title = 'Halaman Depan PPDB';

    protected static ?int $navigationSort = 3;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function getSubheading(): ?string
    {
        return 'Susun isi halaman /ppdb: hero di bagian atas dan seksi-seksi di bawahnya.';
    }

    public function mount(): void
    {
        $this->fillFromSettings();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hero')
                ->description('Bagian paling atas halaman. Gunakan {year} untuk tahun ajaran PPDB dan {site} untuk nama situs.')
                ->icon(Heroicon::OutlinedSparkles)
                ->schema([
                    TextInput::make('hero.badge')
                        ->label('Label Kecil')
                        ->maxLength(80)
                        ->placeholder('PPDB / SPMB {year}')
                        ->helperText('Tampil di atas judul. Kosongkan untuk menyembunyikan.')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('hero.title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('Penerimaan Peserta Didik Baru'),

                        TextInput::make('hero.highlight')
                            ->label('Teks Sorotan')
                            ->maxLength(120)
                            ->placeholder('Tahun Ajaran {year}')
                            ->helperText('Baris kedua judul dengan warna aksen. Opsional.'),
                    ]),

                    Textarea::make('hero.subtitle')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->maxLength(400)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        self::buttonFields('primary', 'Tombol Utama', 'Daftar Sekarang', '#jenjang'),
                        self::buttonFields('secondary', 'Tombol Kedua', 'Cek Status Pendaftaran', '/ppdb/status'),
                    ]),
                ]),

            PageHeroFields::make('ppdb', 'Latar hero halaman /ppdb. Rasio 16:9, lebar optimal 1600px. Kosongkan untuk latar gradasi bawaan.'),

            Section::make('Seksi Halaman')
                ->description('Seksi tampil berurutan dari atas ke bawah — seret untuk mengubah urutan. Seksi data PPDB (Jenjang, Kuota, Alur, Jalur, Jadwal, Biaya, Persyaratan, FAQ) mengambil isinya otomatis dan disembunyikan sendiri selama datanya kosong.')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->schema([
                    PpdbLandingBlocks::make(),
                ]),

            Section::make('SEO')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(2)
                        ->maxLength(160)
                        ->helperText('Ringkasan halaman untuk hasil pencarian Google. Kosongkan untuk memakai kalimat bawaan.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = self::applyPageHero($this->form->getState(), 'PPDB');
        $blocks = self::applyBlockImagePickers($data['blocks'] ?? [], 'PPDB');

        Setting::setMany([
            PpdbLanding::HERO_SETTING => json_encode($data['hero'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PpdbLanding::SECTIONS_SETTING => json_encode(array_values($blocks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PpdbLanding::META_SETTING => trim((string) ($data['meta_description'] ?? '')),
        ]);

        Notification::make()
            ->success()
            ->title('Halaman depan PPDB berhasil disimpan')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),

            Action::make('view')
                ->label('Lihat Halaman')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => route('ppdb.index'))
                ->openUrlInNewTab(),

            Action::make('reset')
                ->label('Kembalikan ke Bawaan')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Kembalikan halaman PPDB ke bawaan?')
                ->modalDescription('Hero dan seluruh seksi yang sudah disusun akan diganti susunan bawaan. Data PPDB (jenjang, jalur, gelombang, FAQ) tidak terpengaruh.')
                ->action(function (): void {
                    Setting::forget([PpdbLanding::HERO_SETTING, PpdbLanding::SECTIONS_SETTING, PpdbLanding::META_SETTING]);

                    $this->fillFromSettings();

                    Notification::make()
                        ->success()
                        ->title('Halaman depan PPDB dikembalikan ke bawaan')
                        ->send();
                }),
        ];
    }

    private function fillFromSettings(): void
    {
        $this->form->fill([
            'hero' => PpdbLanding::hero(),
            'blocks' => PpdbLanding::sections(),
            'meta_description' => (string) Setting::get(PpdbLanding::META_SETTING, ''),
        ]);
    }

    private static function buttonFields(string $key, string $label, string $labelPlaceholder, string $urlPlaceholder): Fieldset
    {
        return Fieldset::make($label)
            ->columns(1)
            ->schema([
                TextInput::make("hero.{$key}_label")
                    ->label('Teks Tombol')
                    ->maxLength(60)
                    ->placeholder($labelPlaceholder)
                    ->helperText('Kosongkan untuk menyembunyikan tombol ini.'),

                TextInput::make("hero.{$key}_url")
                    ->label('Tautan')
                    ->maxLength(255)
                    ->placeholder($urlPlaceholder)
                    ->helperText('URL lengkap, path seperti /ppdb/status, atau anchor seksi seperti #jenjang.')
                    ->rule('regex:/^(https?:\/\/|\/|#|mailto:|tel:)/')
                    ->validationMessages([
                        'regex' => 'Tautan harus diawali http://, https://, /, #, mailto:, atau tel:.',
                    ]),
            ]);
    }
}
