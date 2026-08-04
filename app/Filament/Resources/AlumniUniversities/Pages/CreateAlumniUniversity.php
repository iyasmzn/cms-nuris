<?php

namespace App\Filament\Resources\AlumniUniversities\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\AlumniUniversities\AlumniUniversityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlumniUniversity extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = AlumniUniversityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['logo']);
    }
}
