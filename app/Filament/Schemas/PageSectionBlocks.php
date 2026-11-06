<?php

namespace App\Filament\Schemas;

use App\Models\Album;
use App\Models\ContentSection;
use App\Support\LandingBlocks;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Penyusun seksi Halaman Statis & Program: blok isian manual (teks, gambar,
 * kartu, …) ditambah blok data yang memakai seksi halaman depan — Statistik,
 * Program, Berita, FAQ, Kontak, dan seterusnya — dengan judul & latar per blok.
 */
class PageSectionBlocks
{
    public static function make(string $directory): Repeater
    {
        return ContentBlocks::make(
            $directory,
            sections: true,
            extraTypes: LandingBlocks::TYPES,
            extraFields: self::dataFields(),
            extraTypesGroup: 'Data Otomatis — seperti seksi halaman depan',
        );
    }

    /**
     * Bidang pengaturan blok data. Isinya sendiri tidak diisi di sini,
     * melainkan ditarik dari menu sumbernya.
     *
     * @return list<Component>
     */
    private static function dataFields(): array
    {
        return [
            Callout::make(fn (Get $get): ?string => isset(LandingBlocks::SOURCES[$get('type')])
                ? 'Isi diambil dari '.LandingBlocks::SOURCES[$get('type')].'.'
                : null)
                ->description('Seksi ini disembunyikan sendiri selama datanya kosong. Judul, latar, dan jaraknya tetap diatur di blok ini.')
                ->info()
                ->visible(self::isData())
                ->columnSpanFull(),

            Textarea::make('intro')
                ->label('Teks Pengantar')
                ->rows(2)
                ->maxLength(400)
                ->helperText('Paragraf singkat di bawah judul seksi. Opsional.')
                ->visible(self::isData())
                ->columnSpanFull(),

            // Nilai tersimpan yang tak lagi ada di daftar (jenis bloknya diganti, atau
            // kategori/albumnya sudah tidak dipakai) tetap jadi pilihan, supaya
            // admin melihatnya dan bisa mengosongkannya alih-alih tertahan galat validasi.
            Select::make('data_category')
                ->label('Kategori')
                ->options(fn (Get $get, mixed $state): array => LandingBlocks::categoryOptions($get('type'))
                    + (filled($state) ? [(string) $state => (string) $state] : []))
                ->placeholder('Semua kategori')
                ->native(false)
                ->helperText('Tampilkan hanya item dari satu kategori.')
                ->visible(self::isType(...array_keys(LandingBlocks::CATEGORY_SOURCES)))
                ->columnSpanFull(),

            Select::make('data_album')
                ->label('Album')
                ->options(fn (mixed $state): array => Album::options()
                    + (filled($state) ? [(int) $state => 'Album sudah dihapus'] : []))
                ->placeholder('Semua album')
                ->native(false)
                ->helperText('Tampilkan hanya media dari satu album.')
                ->visible(self::isType('data_gallery'))
                ->columnSpanFull(),

            Select::make('items_limit')
                ->label('Jumlah Ditampilkan')
                ->options(LandingBlocks::LIMITS)
                ->placeholder(fn (Get $get): string => 'Bawaan ('.(LandingBlocks::DEFAULT_LIMITS[$get('type')] ?? 6).' item)')
                ->native(false)
                ->visible(self::isType(...array_keys(LandingBlocks::DEFAULT_LIMITS)))
                ->columnSpanFull(),

            Select::make('data_columns')
                ->label('Kartu Sebaris')
                ->options(ContentSection::ITEM_COLUMNS)
                ->default(LandingBlocks::DEFAULT_COLUMNS)
                ->native(false)
                ->selectablePlaceholder(false)
                ->helperText('Berlaku di layar lebar. Layar ponsel selalu satu kartu (galeri dua), layar sedang paling banyak dua.')
                ->visible(self::isType(...LandingBlocks::GRID_TYPES))
                ->columnSpanFull(),

            Toggle::make('posts_featured')
                ->label('Sorot Artikel Terbaru')
                ->default(true)
                ->onColor('success')
                ->helperText('Artikel paling baru tampil besar di atas grid, seperti di halaman depan.')
                ->visible(self::isType('data_posts'))
                ->columnSpanFull(),

            Toggle::make('show_more_link')
                ->label('Tampilkan Tombol "Lihat Semua"')
                ->default(true)
                ->onColor('success')
                ->helperText('Tombol menuju halaman daftar lengkapnya, di bawah seksi.')
                ->visible(self::isType(...array_keys(LandingBlocks::MORE_LINKS)))
                ->columnSpanFull(),

            Toggle::make('contact_map')
                ->label('Tampilkan Peta')
                ->default(true)
                ->onColor('success')
                ->helperText('Peta hanya muncul bila URL embed Google Maps diisi di Pengaturan Umum.')
                ->visible(self::isType('data_contact')),

            Toggle::make('contact_buttons')
                ->label('Tampilkan Tombol WhatsApp & Email')
                ->default(true)
                ->onColor('success')
                ->visible(self::isType('data_contact')),
        ];
    }

    /**
     * @return Closure(Get): bool
     */
    private static function isData(): Closure
    {
        return fn (Get $get): bool => LandingBlocks::isDataType($get('type'));
    }

    /**
     * @return Closure(Get): bool
     */
    private static function isType(string ...$types): Closure
    {
        return fn (Get $get): bool => in_array($get('type'), $types, true);
    }
}
