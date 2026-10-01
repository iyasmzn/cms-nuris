<?php

namespace App\Filament\Resources\AdmissionPaths\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\AdmissionPaths\AdmissionPathResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmissionPath extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = AdmissionPathResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['image', 'icon_image'], self::imageBaseName($data['name'] ?? null, 'Jalur Pendaftaran'));
    }
}
