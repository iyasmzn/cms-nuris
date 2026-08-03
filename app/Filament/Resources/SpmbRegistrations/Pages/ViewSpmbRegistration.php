<?php

namespace App\Filament\Resources\SpmbRegistrations\Pages;

use App\Filament\Actions\RejectPaymentAction;
use App\Filament\Actions\SettlePaymentAction;
use App\Filament\Actions\UpdateRegistrationStatusAction;
use App\Filament\Resources\SpmbRegistrations\SpmbRegistrationResource;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSpmbRegistration extends ViewRecord
{
    protected static string $resource = SpmbRegistrationResource::class;

    /**
     * The verification workflow lives here as discrete actions, so opening a
     * pendaftar to check their data cannot accidentally overwrite it. Editing
     * the data itself stays behind an explicit button that only users with the
     * `Update` permission ever see.
     *
     * The payment actions are bound to the registration's own tagihan, letting
     * panitia settle a bukti without leaving this page.
     */
    protected function getHeaderActions(): array
    {
        $payment = fn (): ?RegistrationPayment => $this->getRecord()->payment;

        return [
            UpdateRegistrationStatusAction::make(),

            SettlePaymentAction::make()
                ->label('Tandai Lunas')
                ->record($payment)
                ->visible(fn (): bool => $payment() !== null && ! $payment()->isSettled()),

            RejectPaymentAction::make()
                ->label('Tolak Bukti')
                ->record($payment)
                ->visible(fn (): bool => $payment()?->status === RegistrationPayment::STATUS_WAITING),

            EditAction::make()
                ->label('Ubah Data')
                ->color('gray')
                ->outlined(),
        ];
    }

    public function getTitle(): string
    {
        /** @var SpmbRegistration $record */
        $record = $this->getRecord();

        return $record->full_name;
    }

    public function getSubheading(): ?string
    {
        /** @var SpmbRegistration $record */
        $record = $this->getRecord();

        return trim(($record->registration_number ?? '').' · '.($record->institution?->name ?? ''), ' ·') ?: null;
    }
}
