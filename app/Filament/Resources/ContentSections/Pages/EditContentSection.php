<?php

namespace App\Filament\Resources\ContentSections\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\ContentSections\ContentSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentSection extends EditRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = ContentSectionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::applyImagePickers($data, ['image', 'background_image']);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
