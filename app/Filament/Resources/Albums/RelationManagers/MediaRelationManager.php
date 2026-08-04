<?php

namespace App\Filament\Resources\Albums\RelationManagers;

use App\Models\Media;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * The album's contents, shown on its edit page: what is inside, whether each
 * item is published to the public gallery, and a way to add or remove items
 * without leaving the album.
 */
class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Isi Album';

    protected static ?string $modelLabel = 'Media';

    protected static ?string $pluralModelLabel = 'Media';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->paginated([12, 24, 48])
            ->columns([
                ViewColumn::make('preview')
                    ->label('')
                    ->view('filament.media.thumbnail', [
                        'height' => 64,
                        'width' => '64px',
                        'radius' => '.5rem',
                    ]),

                TextColumn::make('name')
                    ->label('Nama')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable()
                    ->description(fn (Media $record): ?string => $record->alt),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->state(fn (Media $record): string => $record->getTypeLabel())
                    ->badge()
                    ->color('gray')
                    ->size(TextSize::ExtraSmall),

                TextColumn::make('show_in_gallery')
                    ->label('Galeri')
                    ->state(fn (Media $record): string => $record->show_in_gallery ? 'Dipublikasikan' : 'Disembunyikan')
                    ->badge()
                    ->color(fn (Media $record): string => $record->show_in_gallery ? 'success' : 'gray')
                    ->icon(fn (Media $record): Heroicon => $record->show_in_gallery ? Heroicon::OutlinedEye : Heroicon::OutlinedEyeSlash)
                    ->size(TextSize::ExtraSmall),

                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('show_in_gallery')
                    ->label('Status Publikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Dipublikasikan')
                    ->falseLabel('Disembunyikan'),
            ])
            ->emptyStateHeading('Album ini masih kosong')
            ->emptyStateDescription('Tambahkan media yang sudah ada lewat tombol di atas, atau pilih album ini saat mengunggah media.')
            ->headerActions([
                AssociateAction::make()
                    ->label('Tambah Media')
                    ->modalHeading('Tambah Media ke Album')
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'alt']),
            ])
            ->recordActions([
                DissociateAction::make()
                    ->label('Keluarkan')
                    ->modalHeading('Keluarkan dari album?')
                    ->modalDescription('Media tetap ada di Media Library, hanya tidak lagi tergabung dalam album ini.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()
                        ->label('Keluarkan dari Album'),
                ]),
            ]);
    }
}
