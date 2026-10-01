<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\Institutions\InstitutionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstitution extends EditRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = InstitutionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['name'] ?? null, 'Jenjang'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
