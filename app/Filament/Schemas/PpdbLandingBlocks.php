<?php

namespace App\Filament\Schemas;

use App\Models\ContentSection;
use App\Models\Faq;
use App\Models\Institution;
use App\Support\PpdbLanding;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Repeater seksi halaman depan PPDB: blok konten biasa (teks, gambar, kartu,
 * CTA, …) ditambah jenis seksi data PPDB beserta bidang pengaturannya.
 */
class PpdbLandingBlocks
{
    public static function make(): Repeater
    {
        return ContentBlocks::make('ppdb', sections: true, extraTypes: PpdbLanding::blockTypes(), extraFields: [
            Textarea::make('intro')
                ->label('Teks Pengantar')
                ->rows(2)
                ->maxLength(400)
                ->helperText('Paragraf singkat di bawah judul seksi. Opsional.')
                ->visible(fn (Get $get): bool => PpdbLanding::isPpdbType($get('type')))
                ->columnSpanFull(),

            ...self::timelineFields(),

            Select::make('institution_id')
                ->label('Ambil Isi dari Jenjang')
                ->options(fn (): array => Institution::query()->ordered()->pluck('name', 'id')->all())
                ->placeholder('Pengaturan PPDB (umum)')
                ->native(false)
                ->helperText('Kosongkan untuk memakai daftar di Pengaturan PPDB. Pilih jenjang untuk menampilkan versi milik jenjang itu.')
                ->visible(fn (Get $get): bool => in_array($get('type'), PpdbLanding::JENJANG_SOURCED_TYPES, true))
                ->columnSpanFull(),

            Toggle::make('show_remaining')
                ->label('Tampilkan jumlah terisi & sisa kuota')
                ->default(true)
                ->onColor('success')
                ->helperText('Hanya untuk jenjang yang memakai formulir internal. Kuota sendiri diisi di menu Jenjang / Unit; jenjang tanpa kuota tidak ikut tampil.')
                ->visible(fn (Get $get): bool => $get('type') === 'ppdb_quota')
                ->columnSpanFull(),

            self::columnsField('institutions_columns', 'ppdb_institutions'),
            self::columnsField('quota_columns', 'ppdb_quota'),
            self::columnsField('procedures_columns', 'ppdb_procedures'),

            Select::make('paths_layout')
                ->label('Tampilan Kartu Jalur')
                ->options(PpdbLanding::PATH_LAYOUTS)
                ->default('auto')
                ->native(false)
                ->selectablePlaceholder(false)
                ->helperText('Kartu Ikon atau Daftar Ringkas cocok bila jalur belum punya gambar sampul — ikonnya tampil besar tanpa bidang gambar yang kosong.')
                ->visible(fn (Get $get): bool => $get('type') === 'ppdb_paths')
                ->columnSpanFull(),

            self::columnsField('paths_columns', 'ppdb_paths', PpdbLanding::DEFAULT_PATH_COLUMNS),

            Select::make('faq_category')
                ->label('Kategori FAQ')
                ->options(fn (): array => Faq::query()
                    ->whereNotNull('category')
                    ->where('category', '!=', '')
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category', 'category')
                    ->all())
                ->placeholder('Semua kategori')
                ->native(false)
                ->helperText('Tampilkan hanya pertanyaan dari satu kategori, misalnya "SPMB".')
                ->visible(fn (Get $get): bool => $get('type') === 'ppdb_faq')
                ->columnSpanFull(),
        ]);
    }

    /**
     * Pilihan "Kartu Sebaris" untuk satu jenis seksi data PPDB. Tanpa jumlah
     * bawaan, pilihannya diawali "Otomatis" (grid yang menyesuaikan layar)
     * dan itulah bawaannya.
     */
    private static function columnsField(string $name, string $type, ?int $default = null): Select
    {
        return Select::make($name)
            ->label('Kartu Sebaris')
            ->options($default === null ? PpdbLanding::autoColumnOptions() : ContentSection::ITEM_COLUMNS)
            ->default($default ?? PpdbLanding::AUTO_COLUMNS)
            ->native(false)
            ->selectablePlaceholder(false)
            ->helperText($default === null
                ? 'Otomatis mengisi baris sebanyak kartu yang muat. Jumlah tetap berlaku di layar lebar; ponsel selalu satu kartu, layar sedang paling banyak dua.'
                : 'Berlaku di layar lebar. Layar ponsel selalu satu kartu, layar sedang paling banyak dua.')
            ->visible(fn (Get $get): bool => $get('type') === $type)
            ->columnSpanFull();
    }

    /**
     * Bidang blok Timeline: daftar titik linimasa dan tampilannya.
     *
     * @return list<Component>
     */
    private static function timelineFields(): array
    {
        $isTimeline = fn (Get $get): bool => $get('type') === 'ppdb_timeline';

        return [
            Select::make('timeline_layout')
                ->label('Tampilan Timeline')
                ->options(PpdbLanding::TIMELINE_LAYOUTS)
                ->default('vertical')
                ->native(false)
                ->selectablePlaceholder(false)
                ->helperText('Di layar ponsel semua tampilan menjadi vertikal agar tetap terbaca.')
                ->visible($isTimeline)
                ->columnSpanFull(),

            Repeater::make('timeline_items')
                ->label('Titik Linimasa')
                ->schema([
                    Grid::make(12)->schema([
                        TextInput::make('icon')
                            ->label('Ikon')
                            ->maxLength(10)
                            ->placeholder('📅')
                            ->hint('Emoji')
                            ->helperText('Kosong = nomor urut.')
                            ->columnSpan(3),

                        TextInput::make('label')
                            ->label('Tanggal / Label')
                            ->maxLength(80)
                            ->placeholder('1 – 31 Januari {year}')
                            ->helperText('Opsional. {year} = tahun ajaran PPDB.')
                            ->columnSpan(9),

                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('Pendaftaran Gelombang 1')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(2)
                            ->maxLength(400)
                            ->columnSpanFull(),

                        Toggle::make('highlighted')
                            ->label('Sorot titik ini')
                            ->default(false)
                            ->onColor('success')
                            ->helperText('Misalnya tahap yang sedang berlangsung.')
                            ->columnSpanFull(),
                    ]),
                ])
                ->addActionLabel('+ Tambah Titik')
                ->reorderableWithDragAndDrop()
                ->collapsible()
                ->defaultItems(1)
                ->minItems(1)
                ->itemLabel(fn (array $state): string => trim(implode(' ', array_filter([
                    $state['icon'] ?? null,
                    filled($state['label'] ?? null) ? $state['label'].' —' : null,
                    $state['title'] ?? 'Titik baru',
                ]))))
                ->visible($isTimeline)
                ->columnSpanFull(),
        ];
    }
}
