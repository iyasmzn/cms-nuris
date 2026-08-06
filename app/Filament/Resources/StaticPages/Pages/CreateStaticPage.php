<?php

namespace App\Filament\Resources\StaticPages\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithPageHero;
use App\Filament\Resources\StaticPages\StaticPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaticPage extends CreateRecord
{
    use InteractsWithImagePicker;
    use InteractsWithPageHero;

    protected static string $resource = StaticPageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $baseName = self::imageBaseName($data['title'] ?? null, 'Halaman');

        $data['blocks'] = self::applyBlockImagePickers($data['blocks'] ?? [], $baseName);

        return self::applyPageHero($data, $baseName);
    }
}
