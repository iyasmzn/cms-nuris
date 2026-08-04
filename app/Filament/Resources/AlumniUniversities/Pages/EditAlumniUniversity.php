<?php

namespace App\Filament\Resources\AlumniUniversities\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\AlumniUniversities\AlumniUniversityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAlumniUniversity extends EditRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = AlumniUniversityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::applyImagePickers($data, ['logo']);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
