<?php

namespace App\Filament\Resources\AlumniUniversities\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AlumniUniversityForm
{
    use InteractsWithImagePicker;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Kampus')
                ->description('Logo kampus ini ikut berjalan pada baris logo di seksi Alumni halaman utama.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Kampus')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('Universitas Gadjah Mada')
                        ->columnSpanFull(),

                    self::imagePicker(
                        key: 'logo',
                        label: 'Logo',
                        hint: 'PNG transparan paling rapi. Akan di-resize ke lebar maks. 300px.',
                        accepted: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
                        width: 300,
                        directory: 'alumni-universities',
                    )->columnSpanFull(),

                    TextInput::make('url')
                        ->label('Tautan Situs Kampus')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://ugm.ac.id')
                        ->hint('Opsional — logo menjadi tautan bila diisi.')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->hint('Angka kecil tampil lebih dulu.'),

                        Toggle::make('is_active')
                            ->label('Tampilkan')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ]),
                ]),
        ]);
    }
}
