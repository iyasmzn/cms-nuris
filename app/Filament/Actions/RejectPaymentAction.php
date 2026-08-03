<?php

namespace App\Filament\Actions;

use App\Models\RegistrationPayment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Reject a bukti transfer with a reason the pendaftar will read on their
 * status page, then re-upload against.
 */
class RejectPaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tolak';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Tolak')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('verify')
            ->modalHeading('Tolak Bukti Pembayaran')
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan Penolakan')
                    ->required()
                    ->rows(3)
                    ->placeholder('Nominal tidak sesuai / bukti tidak terbaca / rekening tujuan salah')
                    ->helperText('Alasan ini ditampilkan ke pendaftar agar mereka dapat mengunggah ulang bukti yang benar.'),
            ])
            ->visible(fn (RegistrationPayment $record): bool => $record->status === RegistrationPayment::STATUS_WAITING)
            ->action(function (array $data, RegistrationPayment $record): void {
                $record->markRejected(auth()->user(), $data['reason']);

                Notification::make()
                    ->warning()
                    ->title('Bukti pembayaran ditolak')
                    ->body('Pendaftar dapat mengunggah ulang bukti transfer.')
                    ->send();
            });
    }
}
