<?php

namespace App\Filament\Resources\FloatingButtons\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\FloatingButtons\FloatingButtonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFloatingButton extends EditRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = FloatingButtonResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['label'] ?? null, 'Floating Button'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
