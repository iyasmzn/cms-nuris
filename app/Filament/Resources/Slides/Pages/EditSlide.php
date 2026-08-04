<?php

namespace App\Filament\Resources\Slides\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithVideoPicker;
use App\Filament\Resources\Slides\SlideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSlide extends EditRecord
{
    use InteractsWithImagePicker;
    use InteractsWithVideoPicker;

    protected static string $resource = SlideResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $baseName = self::imageBaseName($data['title'] ?? null, 'Slide');

        $data = self::applyImagePickers($data, ['image']);
        $data = self::applyVideoPickers($data, ['video_path'], $baseName);

        return self::syncVideoEmbeds($data, ['video_url', 'preview_video_url'], $baseName);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
