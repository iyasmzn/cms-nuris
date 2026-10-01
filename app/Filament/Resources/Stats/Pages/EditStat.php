<?php

namespace App\Filament\Resources\Stats\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\Stats\StatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStat extends EditRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = StatResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['label'] ?? null, 'Statistik'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
