<?php

namespace App\Filament\Resources\RegistrationPayments\Pages;

use App\Filament\Resources\RegistrationPayments\RegistrationPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRegistrationPayment extends EditRecord
{
    protected static string $resource = RegistrationPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
