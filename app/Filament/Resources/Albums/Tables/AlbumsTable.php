<?php

namespace App\Filament\Resources\Albums\Tables;

use App\Models\Album;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class AlbumsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withCount([
                    'media',
                    'media as published_media_count' => fn ($q) => $q->where('show_in_gallery', true),
                ])
                // Newest few items only — enough for the preview strip.
                ->with(['media' => fn ($q) => $q->latest()->limit(4)]))
            ->columns([
                ViewColumn::make('media_preview')
                    ->label('Isi Album')
                    ->view('filament.albums.thumbnail-strip'),

                TextColumn::make('name')
                    ->label('Nama Album')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable()
                    ->description(fn (Album $record): ?string => $record->description),

                TextColumn::make('media_count')
                    ->label('Jumlah Media')
                    ->badge()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->sortable(),

                TextColumn::make('published_media_count')
                    ->label('Tampil di Galeri')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'gray')
                    ->icon(Heroicon::OutlinedEye)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada album')
            ->emptyStateDescription('Album bisa dibuat di sini, atau langsung saat mengunggah media.')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
