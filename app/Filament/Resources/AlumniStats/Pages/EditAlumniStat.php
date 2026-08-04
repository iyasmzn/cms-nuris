<?php

namespace App\Filament\Resources\AlumniStats\Pages;

use App\Filament\Resources\AlumniStats\AlumniStatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAlumniStat extends EditRecord
{
    protected static string $resource = AlumniStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
