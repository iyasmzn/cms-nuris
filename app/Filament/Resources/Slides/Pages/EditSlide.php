<?php

namespace App\Filament\Resources\Slides\Pages;

use App\Filament\Concerns\InteractsWithImagePicker;
use App\Filament\Concerns\InteractsWithVideoPicker;
use App\Filament\Resources\Slides\SlideResource;
use App\Support\HeroTitleEffect;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSlide extends EditRecord
{
    use InteractsWithImagePicker;
    use InteractsWithVideoPicker;

    protected static string $resource = SlideResource::class;

    /**
     * Efek judul disimpan sebagai satu kolom JSON yang boleh null atau hanya
     * terisi sebagian. Dinormalkan dulu di sini supaya kartu "Efek Judul"
     * selalu menemukan nilai yang sah — slide lama pun tetap bisa disimpan
     * tanpa harus memilih animasi terlebih dahulu.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['title_effect'] = HeroTitleEffect::fromArray($data['title_effect'] ?? [])->toFormState();

        return $data;
    }

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
