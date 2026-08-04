<?php

namespace App\Filament\Resources\ContentSections\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\ContentSections\ContentSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentSection extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = ContentSectionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['image']);
    }
}
