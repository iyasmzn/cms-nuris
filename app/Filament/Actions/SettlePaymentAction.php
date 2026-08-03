<?php

namespace App\Filament\Actions;

use App\Models\RegistrationPayment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Mark a tagihan settled. Gated on the `verify` policy rather than `update`,
 * so a verifikator can act on a bukti without being able to alter the nominal.
 */
class SettlePaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'verifikasi';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Lunas')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('verify')
            ->requiresConfirmation()
            ->modalHeading('Tandai Pembayaran Lunas')
            ->modalDescription('Pastikan dana sudah masuk ke rekening dan nominalnya cocok, termasuk kode unik.')
            ->visible(fn (RegistrationPayment $record): bool => ! $record->isSettled())
            ->action(function (RegistrationPayment $record): void {
                $record->markPaid(auth()->user());

                Notification::make()
                    ->success()
                    ->title('Pembayaran ditandai lunas')
                    ->send();
            });
    }
}
