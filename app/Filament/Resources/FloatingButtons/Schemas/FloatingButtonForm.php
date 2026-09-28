<?php

namespace App\Filament\Resources\FloatingButtons\Schemas;

use App\Filament\Support\IconUpload;
use App\Models\FloatingButton;
use App\Support\PageTargets;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FloatingButtonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tombol')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('label')
                                    ->label('Label')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Hubungi Kami'),

                                TextInput::make('icon')
                                    ->label('Ikon (emoji atau teks)')
                                    ->maxLength(100)
                                    ->default('💬')
                                    ->placeholder('💬')
                                    ->hint('Emoji atau karakter singkat yang tampil di tombol.'),
                            ]),

                        IconUpload::make()
                            ->columnSpanFull(),

                        TextInput::make('url')
                            ->label('URL / Link')
                            ->required()
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://wa.me/628123456789')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                ColorPicker::make('color')
                                    ->label('Warna Tombol')
                                    ->default('#08484A'),

                                TextInput::make('sort_order')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0)
                                    ->hint('Urutan tampil (angka kecil = tampil lebih dulu).'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('open_in_new_tab')
                                    ->label('Buka di Tab Baru')
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Tampil di Halaman')
                    ->description('Atur di halaman mana tombol ini muncul. Contoh: tombol WA panitia yang berbeda untuk halaman PPDB tiap jenjang.')
                    ->schema([
                        ToggleButtons::make('display_mode')
                            ->label('Tampilkan di')
                            ->options(FloatingButton::displayModeOptions())
                            ->icons([
                                FloatingButton::DISPLAY_ALL => Heroicon::OutlinedGlobeAlt,
                                FloatingButton::DISPLAY_ONLY => Heroicon::OutlinedCheckCircle,
                                FloatingButton::DISPLAY_EXCEPT => Heroicon::OutlinedNoSymbol,
                            ])
                            ->default(FloatingButton::DISPLAY_ALL)
                            ->required()
                            ->inline()
                            ->live()
                            ->columnSpanFull(),

                        Select::make('display_targets')
                            ->label(fn (Get $get): string => $get('display_mode') === FloatingButton::DISPLAY_EXCEPT
                                ? 'Sembunyikan di halaman'
                                : 'Tampilkan hanya di halaman')
                            ->options(fn (): array => PageTargets::options())
                            ->multiple()
                            ->searchable()
                            ->formatStateUsing(fn (?array $state): array => PageTargets::onlyKnown($state ?? []))
                            ->helperText('"PPDB (semua jenjang)" mencakup seluruh halaman PPDB. Pilihan per jenjang hanya berlaku di halaman PPDB jenjang itu, termasuk halaman status & pembayaran pendaftarnya.')
                            ->required(fn (Get $get): bool => $get('display_mode') !== FloatingButton::DISPLAY_ALL)
                            ->visible(fn (Get $get): bool => $get('display_mode') !== FloatingButton::DISPLAY_ALL)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
