<?php

namespace App\Filament\Resources\Institutions\Tables;

use App\Models\Institution;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstitutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Jenjang')
                    ->badge()
                    ->color(fn (Institution $record): string => $record->color)
                    ->formatStateUsing(fn (Institution $record): string => trim("{$record->icon} {$record->name}"))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('short_name')
                    ->label('Singkatan')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('waves_count')
                    ->label('Gelombang')
                    ->counts('waves')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('registrations_count')
                    ->label('Pendaftar')
                    ->counts('registrations')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('registration_fee')
                    ->label('Biaya Daftar')
                    ->state(fn (Institution $record): string => (int) ($record->registration_fee ?? 0) > 0
                        ? rupiah($record->registration_fee)
                        : 'Belum diisi')
                    ->badge()
                    ->color(fn (Institution $record): string => (int) ($record->registration_fee ?? 0) > 0 ? 'success' : 'gray')
                    ->description(fn (Institution $record): ?string => setting_bool('spmb_payment_enabled', false) && (int) ($record->registration_fee ?? 0) <= 0
                        ? 'Tagihan tidak terbit'
                        : null)
                    ->visible(fn (): bool => setting_bool('spmb_payment_enabled', false)),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label('Hanya Aktif')
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
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
