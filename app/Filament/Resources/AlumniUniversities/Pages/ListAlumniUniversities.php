<?php

namespace App\Filament\Resources\AlumniUniversities\Pages;

use App\Filament\Resources\AlumniUniversities\AlumniUniversityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAlumniUniversities extends ListRecords
{
    protected static string $resource = AlumniUniversityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
