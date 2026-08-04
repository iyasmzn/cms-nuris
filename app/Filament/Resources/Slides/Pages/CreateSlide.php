<?php

namespace App\Filament\Resources\Slides\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithVideoPicker;
use App\Filament\Resources\Slides\SlideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSlide extends CreateRecord
{
    use InteractsWithImagePicker;
    use InteractsWithVideoPicker;

    protected static string $resource = SlideResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $baseName = self::imageBaseName($data['title'] ?? null, 'Slide');

        $data = self::applyImagePickers($data, ['image']);
        $data = self::applyVideoPickers($data, ['video_path'], $baseName);

        return self::syncVideoEmbeds($data, ['video_url', 'preview_video_url'], $baseName);
    }
}
