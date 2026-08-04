<?php

namespace App\Filament\Resources\ContentSections\Schemas;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Models\ContentSection;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ContentSectionForm
{
    use InteractsWithImagePicker;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Konten')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->schema([
                    TextInput::make('eyebrow')
                        ->label('Label Kecil')
                        ->maxLength(60)
                        ->placeholder('Fasilitas Kami')
                        ->helperText('Teks kecil di atas judul. Kosongkan untuk menyembunyikan.')
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('Lingkungan Belajar yang Nyaman')
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Deskripsi')
                        ->required()
                        ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo'])
                        ->placeholder('Jelaskan seksi ini dalam beberapa kalimat...')
                        ->columnSpanFull(),
                ]),

            Section::make('Gambar & Tata Letak')
                ->icon(Heroicon::OutlinedPhoto)
                ->schema([
                    self::imagePicker(
                        key: 'image',
                        label: 'Gambar Seksi',
                        hint: 'Rasio 4:3. Akan di-resize ke 1000×750. Kosongkan bila seksi tampil tanpa gambar.',
                        accepted: ['image/jpeg', 'image/png', 'image/webp'],
                        width: 1000,
                        height: 750,
                        directory: 'content-sections',
                        aspectRatio: '4:3',
                    )->columnSpanFull(),

                    Grid::make(2)->schema([
                        ToggleButtons::make('image_position')
                            ->label('Posisi Gambar')
                            ->options(ContentSection::IMAGE_POSITIONS)
                            ->icons([
                                'left' => Heroicon::OutlinedArrowLeftCircle,
                                'right' => Heroicon::OutlinedArrowRightCircle,
                            ])
                            ->default('right')
                            ->required()
                            ->inline()
                            ->helperText('Di layar ponsel gambar selalu tampil di atas teks.'),

                        ToggleButtons::make('background')
                            ->label('Latar Seksi')
                            ->options(ContentSection::BACKGROUNDS)
                            ->default('default')
                            ->required()
                            ->inline()
                            ->helperText('Selang-seling latar agar antar seksi tidak menyatu.'),
                    ]),
                ]),

            Section::make('Tombol CTA')
                ->description('Opsional. Tombol muncul hanya bila teks dan tautannya terisi.')
                ->icon(Heroicon::OutlinedCursorArrowRays)
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('cta_label')
                            ->label('Teks Tombol')
                            ->maxLength(60)
                            ->placeholder('Selengkapnya')
                            ->requiredWith('cta_url'),

                        TextInput::make('cta_url')
                            ->label('Tautan Tombol')
                            ->maxLength(255)
                            ->placeholder('https://... atau /profil')
                            ->helperText('Boleh URL lengkap atau path internal seperti /profil.')
                            ->requiredWith('cta_label')
                            ->rule('regex:/^(https?:\/\/|\/|#|mailto:|tel:)/')
                            ->validationMessages([
                                'regex' => 'Tautan harus diawali http://, https://, /, #, mailto:, atau tel:.',
                            ]),

                        Toggle::make('cta_new_tab')
                            ->label('Buka di Tab Baru')
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
                ]),

            Section::make('Pengaturan Tampil')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('anchor')
                            ->label('ID Anchor')
                            ->maxLength(60)
                            ->placeholder('fasilitas')
                            ->helperText('Untuk tautan menu, misal #fasilitas. Kosongkan bila tidak perlu.'),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->hint('Angka kecil tampil lebih dulu.'),

                        Toggle::make('is_published')
                            ->label('Tampilkan di Website')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ]),
                ]),
        ]);
    }
}
