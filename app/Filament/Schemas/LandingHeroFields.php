<?php

namespace App\Filament\Schemas;

use App\Support\LandingHero;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Bidang teks hero ala halaman depan — label kecil, judul, sorotan, deskripsi,
 * dan dua tombol. Dipakai halaman /ppdb (selalu hero landing) serta Halaman
 * Statis & Program (opsional lewat pilihan "Gaya Kepala Halaman"). Semua
 * nilainya tersimpan di bawah kunci `hero`.
 */
class LandingHeroFields
{
    /**
     * Pilihan kepala Halaman & Program: standar (pita judul + breadcrumb) atau
     * hero landing beserta teksnya.
     */
    public static function section(): Section
    {
        $isLanding = fn (Get $get): bool => $get('hero.layout') === LandingHero::LAYOUT_LANDING;

        return Section::make('Kepala Halaman')
            ->description('Standar: pita judul dengan breadcrumb. Hero Landing: hero besar seperti halaman depan — label kecil, judul, sorotan, deskripsi, dan tombol — di atas Cover Hero di bawah ini.')
            ->icon(Heroicon::OutlinedSparkles)
            ->collapsible()
            ->schema([
                ToggleButtons::make('hero.layout')
                    ->label('Gaya Kepala Halaman')
                    ->options(LandingHero::LAYOUTS)
                    ->icons([
                        LandingHero::LAYOUT_STANDARD => Heroicon::OutlinedBars3BottomLeft,
                        LandingHero::LAYOUT_LANDING => Heroicon::OutlinedSparkles,
                    ])
                    ->default(LandingHero::LAYOUT_STANDARD)
                    ->inline()
                    ->live()
                    ->columnSpanFull(),

                Grid::make(1)
                    ->visible($isLanding)
                    ->schema([
                        ...self::textFields(
                            placeholders: [
                                'badge' => 'Selamat Datang',
                                'title' => 'Kosongkan untuk memakai judul halaman',
                                'highlight' => 'Unggul dan Berakhlak',
                                'primary_label' => 'Daftar Sekarang',
                                'primary_url' => '/ppdb',
                                'secondary_label' => 'Hubungi Kami',
                                'secondary_url' => '#kontak',
                            ],
                            titleRequired: false,
                        ),

                        Grid::make(2)->schema([
                            Select::make('hero.align')
                                ->label('Perataan Isi')
                                ->options(LandingHero::ALIGNMENTS)
                                ->default('left')
                                ->native(false)
                                ->selectablePlaceholder(false),

                            Select::make('hero.size')
                                ->label('Tinggi Hero')
                                ->options(LandingHero::SIZES)
                                ->default('lg')
                                ->native(false)
                                ->selectablePlaceholder(false),
                        ]),
                    ]),
            ]);
    }

    /**
     * @param  array{badge: string, title: string, highlight: string, primary_label: string, primary_url: string, secondary_label: string, secondary_url: string}  $placeholders
     * @return list<Component>
     */
    public static function textFields(array $placeholders, bool $titleRequired): array
    {
        return [
            TextInput::make('hero.badge')
                ->label('Label Kecil')
                ->maxLength(80)
                ->placeholder($placeholders['badge'])
                ->helperText('Tampil di atas judul. Kosongkan untuk menyembunyikan.')
                ->columnSpanFull(),

            Grid::make(2)->schema([
                TextInput::make('hero.title')
                    ->label('Judul')
                    ->required($titleRequired)
                    ->maxLength(120)
                    ->placeholder($placeholders['title']),

                TextInput::make('hero.highlight')
                    ->label('Teks Sorotan')
                    ->maxLength(120)
                    ->placeholder($placeholders['highlight'])
                    ->helperText('Baris kedua judul dengan warna aksen. Opsional.'),
            ]),

            Textarea::make('hero.subtitle')
                ->label('Deskripsi')
                ->rows(3)
                ->maxLength(400)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                self::buttonFields('primary', 'Tombol Utama', $placeholders['primary_label'], $placeholders['primary_url']),
                self::buttonFields('secondary', 'Tombol Kedua', $placeholders['secondary_label'], $placeholders['secondary_url']),
            ]),
        ];
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
                    ->helperText('URL lengkap, path seperti /ppdb/status, atau anchor seksi seperti #kontak.')
                    ->rule('regex:/^(https?:\/\/|\/|#|mailto:|tel:)/')
                    ->validationMessages([
                        'regex' => 'Tautan harus diawali http://, https://, /, #, mailto:, atau tel:.',
                    ]),
            ]);
    }
}
