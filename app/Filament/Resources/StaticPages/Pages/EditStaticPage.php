<?php

namespace App\Filament\Resources\StaticPages\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithPageHero;
use App\Filament\Resources\StaticPages\StaticPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaticPage extends EditRecord
{
    use InteractsWithImagePicker;
    use InteractsWithPageHero;

    protected static string $resource = StaticPageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $baseName = self::imageBaseName($data['title'] ?? null, 'Halaman');

        $data['blocks'] = self::applyBlockImagePickers($data['blocks'] ?? [], $baseName);

        return self::applyPageHero($data, $baseName);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
