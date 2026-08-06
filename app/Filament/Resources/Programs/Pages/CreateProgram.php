<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithPageHero;
use App\Filament\Resources\Programs\ProgramResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProgram extends CreateRecord
{
    use InteractsWithImagePicker;
    use InteractsWithPageHero;

    protected static string $resource = ProgramResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = self::applyImagePickers($data, ['image']);

        $baseName = self::imageBaseName($data['title'] ?? null, 'Program');

        $data['blocks'] = self::applyBlockImagePickers($data['blocks'] ?? [], $baseName);

        return self::applyPageHero($data, $baseName);
    }
}
