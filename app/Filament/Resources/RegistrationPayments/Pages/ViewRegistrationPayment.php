<?php

namespace App\Filament\Resources\RegistrationPayments\Pages;

use App\Filament\Actions\RejectPaymentAction;
use App\Filament\Actions\SettlePaymentAction;
use App\Filament\Resources\RegistrationPayments\RegistrationPaymentResource;
use App\Models\RegistrationPayment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRegistrationPayment extends ViewRecord
{
    protected static string $resource = RegistrationPaymentResource::class;

    /**
     * Read-only by default: verifying a bukti should never risk overwriting
     * the nominal or kode unik. Correcting those stays behind an explicit
     * "Ubah Data" button that only `Update` permission holders can see.
     */
    protected function getHeaderActions(): array
    {
        return [
            SettlePaymentAction::make(),
            RejectPaymentAction::make(),

            EditAction::make()
                ->label('Ubah Data')
                ->color('gray')
                ->outlined(),
        ];
    }

    public function getTitle(): string
    {
        /** @var RegistrationPayment $record */
        $record = $this->getRecord();

        return $record->invoice_number ?? 'Tagihan';
    }

    public function getSubheading(): ?string
    {
        /** @var RegistrationPayment $record */
        $record = $this->getRecord();

        return trim(($record->registration?->full_name ?? '').' · '.rupiah($record->total()), ' ·') ?: null;
    }
}
