<?php

namespace App\Filament\Concerns;

use App\Filament\Schemas\PageHeroFields;

/**
 * Menyelesaikan pilihan media cover hero sebelum disimpan: gambar & video di
 * dalam array `hero` berasal dari picker yang menitipkan kunci bantu, dan
 * embed-nya ikut disalin ke Media.
 */
trait InteractsWithPageHero
{
    use InteractsWithImagePicker;
    use InteractsWithVideoPicker;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function applyPageHero(array $data, string $baseName): array
    {
        $hero = is_array($data['hero'] ?? null) ? $data['hero'] : [];

        $hero = self::applyImagePickers($hero, PageHeroFields::IMAGE_KEYS, $baseName);
        $hero = self::applyVideoPickers($hero, PageHeroFields::VIDEO_KEYS, $baseName);

        $data['hero'] = self::syncVideoEmbeds($hero, PageHeroFields::EMBED_KEYS, $baseName);

        return $data;
    }
}
