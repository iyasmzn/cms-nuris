<?php

namespace App\Filament\Resources\Albums\Schemas;

use App\Models\Album;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AlbumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Album')
                ->description('Nama album tampil sebagai filter di halaman Galeri publik.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Album')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('Contoh: Wisuda 2025, Fasilitas')
                        ->unique(Album::class, 'name', ignoreRecord: true)
                        ->validationMessages(['unique' => 'Album dengan nama ini sudah ada.'])
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder('Keterangan singkat tentang album ini…')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
