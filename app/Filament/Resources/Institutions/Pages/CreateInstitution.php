<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\Institutions\InstitutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitution extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = InstitutionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['name'] ?? null, 'Jenjang'));
    }
}
