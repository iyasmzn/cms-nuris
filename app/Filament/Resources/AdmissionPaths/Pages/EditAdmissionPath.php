<?php

namespace App\Filament\Resources\AdmissionPaths\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\AdmissionPaths\AdmissionPathResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionPath extends EditRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = AdmissionPathResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::applyImagePickers($data, ['image'], self::imageBaseName($data['name'] ?? null, 'Jalur Pendaftaran'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
