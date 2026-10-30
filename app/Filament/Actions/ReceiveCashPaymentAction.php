<?php

namespace App\Filament\Actions;

use App\Models\RegistrationPayment;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Record biaya pendaftaran a pendaftar paid tunai langsung di kantor, which
 * settles the tagihan without any bukti transfer. Gated on the `verify` policy
 * like the other settlement actions, and only offered when the jenjang takes
 * cash at all.
 */
class ReceiveCashPaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'terimaTunai';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Terima Tunai')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('primary')
            ->authorize('verify')
            ->modalHeading('Terima Pembayaran Tunai')
            ->modalDescription(fn (RegistrationPayment $record): string => 'Terima uang tunai sebesar '.rupiah($record->amount).'. Kode unik hanya berlaku untuk transfer, jadi tidak ikut dibayar.')
            ->modalSubmitActionLabel('Catat Lunas')
            ->fillForm(fn (RegistrationPayment $record): array => [
                'payer_name' => $record->registration?->parent_name ?: $record->registration?->full_name,
                'paid_on' => now()->toDateString(),
            ])
            ->schema([
                TextInput::make('payer_name')
                    ->label('Nama Penyetor')
                    ->required()
                    ->maxLength(100)
                    ->helperText('Orang yang menyerahkan uang, biasanya orang tua atau wali.'),

                DatePicker::make('paid_on')
                    ->label('Tanggal Diterima')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now()),

                Textarea::make('note')
                    ->label('Catatan')
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder('No. kwitansi 0123 / diterima di loket TU'),
            ])
            ->visible(fn (RegistrationPayment $record): bool => $record->acceptsCash())
            ->action(function (array $data, RegistrationPayment $record): void {
                $record->recordCashPayment([
                    'payer_name' => $data['payer_name'],
                    'paid_on' => $data['paid_on'],
                    'note' => $data['note'] ?? null,
                ], auth()->user());

                Notification::make()
                    ->success()
                    ->title('Pembayaran tunai dicatat')
                    ->body(rupiah($record->amount).' diterima dari '.$data['payer_name'].'.')
                    ->send();
            });
    }
}
