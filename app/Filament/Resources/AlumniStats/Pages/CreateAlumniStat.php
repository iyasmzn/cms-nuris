<?php

namespace App\Filament\Resources\AlumniStats\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\AlumniStats\AlumniStatResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlumniStat extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = AlumniStatResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['label'] ?? null, 'Statistik Alumni'));
    }
}
