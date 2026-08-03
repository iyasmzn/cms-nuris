<?php

namespace App\Filament\Resources\RegistrationPayments\Tables;

use App\Filament\Actions\RejectPaymentAction;
use App\Filament\Actions\SettlePaymentAction;
use App\Filament\Resources\RegistrationPayments\RegistrationPaymentResource;
use App\Models\RegistrationPayment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class RegistrationPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['registration.institution', 'verifier']))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Tagihan')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('registration.full_name')
                    ->label('Pendaftar')
                    ->searchable()
                    ->sortable()
                    ->description(fn (RegistrationPayment $record): string => $record->registration?->registration_number ?? '—'),

                TextColumn::make('registration.institution.short_name')
                    ->label('Jenjang')
                    ->state(fn (RegistrationPayment $record): ?string => $record->registration?->institution?->short_name ?? $record->registration?->institution?->name)
                    ->badge()
                    ->color(fn (RegistrationPayment $record): string => $record->registration?->institution?->color ?? 'gray')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Nominal')
                    ->state(fn (RegistrationPayment $record): string => rupiah($record->total()))
                    ->description(fn (RegistrationPayment $record): ?string => $record->unique_code > 0
                        ? rupiah($record->amount).' + '.$record->unique_code
                        : null)
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RegistrationPayment::STATUS_UNPAID => 'warning',
                        RegistrationPayment::STATUS_WAITING => 'info',
                        RegistrationPayment::STATUS_PAID => 'success',
                        RegistrationPayment::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => RegistrationPayment::statusOptions()[$state] ?? $state),

                TextColumn::make('sender_name')
                    ->label('Pengirim')
                    ->searchable()
                    ->placeholder('—')
                    ->description(fn (RegistrationPayment $record): ?string => $record->transferred_on?->translatedFormat('d M Y')),

                TextColumn::make('submitted_at')
                    ->label('Bukti Dikirim')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('verifier.name')
                    ->label('Diverifikasi Oleh')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tgl. Tagihan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(RegistrationPayment::statusOptions())
                    ->native(false),

                SelectFilter::make('institution')
                    ->label('Jenjang')
                    ->relationship('registration.institution', 'name')
                    ->native(false),
            ])
            ->recordUrl(fn (RegistrationPayment $record): string => RegistrationPaymentResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('lihatBukti')
                    ->label('Bukti')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->color('gray')
                    ->url(fn (RegistrationPayment $record): string => route('ppdb.payment.download', $record))
                    ->openUrlInNewTab()
                    ->authorize('view')
                    ->visible(fn (RegistrationPayment $record): bool => filled($record->proof_path)),

                SettlePaymentAction::make(),
                RejectPaymentAction::make(),

                ViewAction::make()->label('Detail'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('verifikasiMassal')
                        ->label('Tandai Lunas')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai Pembayaran Lunas')
                        ->modalDescription('Semua tagihan terpilih yang belum lunas akan ditandai lunas.')
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->can('Verify:RegistrationPayment') ?? false)
                        ->action(function (Collection $records): void {
                            $verifier = auth()->user();
                            $settled = $records->reject(fn (RegistrationPayment $record): bool => $record->isSettled());

                            $settled->each(fn (RegistrationPayment $record) => $record->markPaid($verifier));

                            Notification::make()
                                ->success()
                                ->title("{$settled->count()} pembayaran ditandai lunas")
                                ->send();
                        }),
                ]),
            ]);
    }
}
