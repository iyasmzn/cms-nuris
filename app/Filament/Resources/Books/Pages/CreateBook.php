<?php

namespace App\Filament\Resources\Books\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Resources\Books\BookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBook extends CreateRecord
{
    use InteractsWithImagePicker;

    protected static string $resource = BookResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::applyImagePickers($data, ['cover_image']);
    }
}
