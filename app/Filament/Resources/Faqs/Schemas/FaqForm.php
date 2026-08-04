<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Models\Faq;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pertanyaan & Jawaban')
                ->schema([
                    TextInput::make('question')
                        ->label('Pertanyaan')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Kapan pendaftaran peserta didik baru dibuka?')
                        ->columnSpanFull(),

                    RichEditor::make('answer')
                        ->label('Jawaban')
                        ->required()
                        ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo'])
                        ->placeholder('Tuliskan jawaban yang singkat dan jelas...')
                        ->columnSpanFull(),
                ]),

            Section::make('Pengaturan Tampil')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('category')
                            ->label('Kategori')
                            ->maxLength(60)
                            ->placeholder('SPMB')
                            ->datalist(fn (): array => self::usedCategories())
                            ->hint('Opsional.')
                            ->helperText('Dipakai sebagai filter di halaman depan. Kosongkan bila tidak perlu.'),

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

    /**
     * Kategori yang sudah dipakai, agar admin konsisten memakai nama yang sama.
     *
     * @return array<int, string>
     */
    private static function usedCategories(): array
    {
        return Faq::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
