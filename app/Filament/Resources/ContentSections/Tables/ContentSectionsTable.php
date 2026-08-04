<?php

namespace App\Filament\Resources\ContentSections\Tables;

use App\Models\ContentSection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ContentSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(40),

                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->width(56)
                    ->height(42),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (ContentSection $record): string => Str::limit(strip_tags($record->description), 90)),

                TextColumn::make('image_position')
                    ->label('Posisi Gambar')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ContentSection::IMAGE_POSITIONS[$state] ?? '—')
                    ->sortable(),

                TextColumn::make('background')
                    ->label('Latar')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ContentSection::BACKGROUNDS[$state] ?? '—')
                    ->description(fn (ContentSection $record): ?string => self::backgroundEffects($record))
                    ->sortable(),

                TextColumn::make('cta_label')
                    ->label('Tombol')
                    ->placeholder('—')
                    ->description(fn (ContentSection $record): ?string => $record->cta_url)
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label('Tampil')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('image_position')
                    ->label('Posisi Gambar')
                    ->options(ContentSection::IMAGE_POSITIONS),

                SelectFilter::make('background')
                    ->label('Jenis Latar')
                    ->options(ContentSection::BACKGROUNDS),

                Filter::make('is_published')
                    ->label('Ditampilkan saja')
                    ->query(fn (Builder $query) => $query->where('is_published', true)),

                Filter::make('not_published')
                    ->label('Disembunyikan')
                    ->query(fn (Builder $query) => $query->where('is_published', false)),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada seksi')
            ->emptyStateDescription('Tambahkan seksi bergambar untuk melengkapi halaman depan. Urutannya diatur di Pengaturan → Halaman Depan.');
    }

    /**
     * Ringkasan efek latar gambar, misal "Blur Sedang · Gelap · Parallax".
     */
    private static function backgroundEffects(ContentSection $record): ?string
    {
        if (! $record->has_background_image) {
            return null;
        }

        $effects = [];

        if ($record->background_blur > 0) {
            $effects[] = 'Blur '.(ContentSection::BLUR_LEVELS[$record->background_blur] ?? $record->background_blur.'px');
        }

        if ($record->background_overlay > 0) {
            $effects[] = ContentSection::OVERLAY_LEVELS[$record->background_overlay] ?? $record->background_overlay.'%';
        }

        $effects[] = match ($record->background_parallax_mode) {
            'scroll' => 'Parallax '.$record->background_parallax_speed.'%',
            'fixed' => 'Latar Diam',
            default => null,
        };

        $effects = array_filter($effects);

        return $effects === [] ? 'Tanpa efek' : implode(' · ', $effects);
    }
}
