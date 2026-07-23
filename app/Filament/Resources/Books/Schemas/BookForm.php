<?php

namespace App\Filament\Resources\Books\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BookForm
{
    use InteractsWithImagePicker;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Buku')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Buku')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                                'slug',
                                Str::slug($state ?? ''),
                            ))
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Slug URL')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('author')
                                    ->label('Penulis / Pengarang')
                                    ->maxLength(150),

                                TextInput::make('isbn')
                                    ->label('ISBN')
                                    ->maxLength(30),

                                TextInput::make('publisher')
                                    ->label('Penerbit')
                                    ->maxLength(150),

                                TextInput::make('published_year')
                                    ->label('Tahun Terbit')
                                    ->numeric()
                                    ->minValue(1800)
                                    ->maxValue(now()->year),
                            ]),

                        Select::make('category')
                            ->label('Kategori')
                            ->options(fn () => Category::optionsForType(Category::TYPE_BOOK))
                            ->native(false),

                        Textarea::make('description')
                            ->label('Deskripsi Buku')
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Gambar Sampul')
                    ->schema([
                        self::imagePicker(
                            key: 'cover_image',
                            label: 'Gambar Sampul',
                            hint: 'Rasio 3:4 (potret). Akan di-resize ke 600×800.',
                            accepted: ['image/jpeg', 'image/png', 'image/webp'],
                            width: 600,
                            height: 800,
                            directory: 'books/covers',
                            aspectRatio: '3:4',
                        )->columnSpanFull(),
                    ]),

                Section::make('Harga & Stok')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('price')
                                    ->label('Harga (Rp)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('Rp'),

                                TextInput::make('stock')
                                    ->label('Stok')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('weight_gram')
                                    ->label('Berat (gram)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('gram'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('pages')
                                    ->label('Jumlah Halaman')
                                    ->numeric()
                                    ->minValue(1)
                                    ->suffix('hal'),

                                TextInput::make('sort_order')
                                    ->label('Urutan Tampil')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Toggle::make('is_available')
                            ->label('Tersedia untuk Dibeli')
                            ->default(true),
                    ]),
            ]);
    }
}
