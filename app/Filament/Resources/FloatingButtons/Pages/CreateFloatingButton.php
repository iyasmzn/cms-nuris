<?php

namespace App\Filament\Resources\FloatingButtons\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\FloatingButtons\FloatingButtonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFloatingButton extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = FloatingButtonResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['label'] ?? null, 'Floating Button'));
    }
}
