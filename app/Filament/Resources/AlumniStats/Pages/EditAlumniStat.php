<?php

namespace App\Filament\Resources\AlumniStats\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\AlumniStats\AlumniStatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAlumniStat extends EditRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = AlumniStatResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::applyImagePickers($data, ['icon_image'], self::imageBaseName($data['label'] ?? null, 'Statistik Alumni'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
