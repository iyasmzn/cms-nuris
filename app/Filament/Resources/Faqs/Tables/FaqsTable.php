<?php

namespace App\Filament\Resources\Faqs\Tables;

use App\Models\Faq;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(40),

                TextColumn::make('question')
                    ->label('Pertanyaan')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Faq $record): string => Str::limit(strip_tags($record->answer), 90)),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

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
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(fn (): array => Faq::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),

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
            ]);
    }
}
