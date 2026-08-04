<?php

namespace App\Filament\Resources\AlumniStats\Pages;

use App\Filament\Resources\AlumniStats\AlumniStatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAlumniStats extends ListRecords
{
    protected static string $resource = AlumniStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
