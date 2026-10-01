<?php

namespace App\Filament\Resources\Stats\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\Stats\StatResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStat extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = StatResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['label'] ?? null, 'Statistik'));
    }
}
