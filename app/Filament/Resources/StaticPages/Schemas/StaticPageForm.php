<?php

namespace App\Filament\Resources\StaticPages\Schemas;

use App\Filament\Schemas\ContentBlocks;
use App\Models\StaticPage;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaticPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Halaman')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Halaman')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                                'slug',
                                Str::slug($state ?? ''),
                            ))
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Slug URL')
                            ->required()
                            ->maxLength(200)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash', Rule::notIn(StaticPage::reservedSlugs())])
                            ->validationMessages([
                                'not_in' => 'Slug ini sudah dipakai bagian lain situs (mis. blog, ppdb, admin). Gunakan slug lain.',
                            ])
                            ->hint('Digunakan sebagai URL halaman. Harus unik dan hanya boleh huruf, angka, dan tanda hubung.')
                            ->helperText(fn (?string $state): string => $state ? url('/'.$state) : '')
                            ->columnSpanFull(),

                        TextInput::make('meta_description')
                            ->label('Meta Deskripsi')
                            ->maxLength(300)
                            ->hint('Maksimal 300 karakter. Digunakan untuk SEO.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Konten')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Isi Halaman')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('pages/attachments')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),
                    ]),

                Section::make('Konten Tambahan')
                    ->description('Tambahkan blok gambar opsional yang ditampilkan di bawah konten utama.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        ContentBlocks::make('pages/blocks'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Pengaturan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sort_order')
                                    ->label('Urutan Tampil')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(9999)
                                    ->default(0)
                                    ->hint('Angka lebih kecil ditampilkan lebih awal.'),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),
                    ])
                    ->columns(1),
            ]);
    }
}
