<?php

namespace App\Filament\Concerns;

use App\Models\Media;
use App\Services\MediaLibraryService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Video counterpart of {@see InteractsWithImagePicker}: choose an existing
 * video from the media library or upload a new file. Uploads are registered in
 * the media library on save so every video used in the app is manageable from
 * one place.
 *
 * Backing form keys per field `$key`:
 *  - `$key`            — the stored file path
 *  - `{$key}_source`   — 'upload' | 'library'
 *  - `{$key}_library`  — selected Media id (library mode)
 */
trait InteractsWithVideoPicker
{
    /**
     * File types accepted for uploaded videos — the formats browsers can play
     * inline without a plugin.
     */
    protected const VIDEO_MIME_TYPES = ['video/mp4', 'video/webm', 'video/ogg'];

    protected static function videoPicker(
        string $key,
        string $label,
        string $hint,
        string $directory = 'slides',
        int $maxSizeKb = 20480,
    ): Fieldset {
        $isUpload = fn (Get $get): bool => ($get("{$key}_source") ?? 'upload') === 'upload';
        $isLibrary = fn (Get $get): bool => $get("{$key}_source") === 'library';

        return Fieldset::make($label)->schema([
            ToggleButtons::make("{$key}_source")
                ->label('Sumber Video')
                ->options([
                    'upload' => 'Upload Baru',
                    'library' => 'Pilih dari Media',
                ])
                ->icons([
                    'upload' => Heroicon::OutlinedArrowUpTray,
                    'library' => Heroicon::OutlinedVideoCamera,
                ])
                ->default('upload')
                ->inline()
                ->live()
                ->columnSpanFull(),

            Select::make("{$key}_library")
                ->label('Pilih dari Media')
                ->options(fn (): array => static::videoLibraryOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->live()
                ->placeholder('Cari nama video…')
                ->helperText('Hanya menampilkan berkas video yang sudah ada di Media.')
                ->visible($isLibrary)
                ->required($isLibrary)
                ->columnSpanFull(),

            Placeholder::make("{$key}_preview")
                ->label('Pratinjau')
                ->visible(fn (Get $get): bool => $isLibrary($get) && filled($get("{$key}_library")))
                ->content(function (Get $get) use ($key): ?HtmlString {
                    $media = Media::find($get("{$key}_library"));

                    if (! $media) {
                        return null;
                    }

                    return new HtmlString(
                        '<video src="'.e($media->url).'" controls muted playsinline '
                        .'style="max-height:200px;max-width:100%;border-radius:.75rem;border:1px solid rgba(0,0,0,.1);"></video>'
                    );
                })
                ->columnSpanFull(),

            FileUpload::make($key)
                ->label('Unggah Video')
                ->disk('public')
                ->directory($directory)
                ->visibility('public')
                ->acceptedFileTypes(self::VIDEO_MIME_TYPES)
                ->maxSize($maxSizeKb)
                ->visible($isUpload)
                ->hint($hint)
                ->columnSpanFull(),
        ]);
    }

    /**
     * Resolve each video field from its chosen source and register the result
     * in the media library.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected static function applyVideoPickers(array $data, array $keys, ?string $baseName = null): array
    {
        foreach ($keys as $key) {
            $data = static::resolveVideoField($data, $key, $baseName);
        }

        return $data;
    }

    /**
     * Register external video URLs held in the given form keys as embed items
     * in the media library, leaving the data untouched.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected static function syncVideoEmbeds(array $data, array $keys, ?string $baseName = null): array
    {
        foreach ($keys as $key) {
            if (filled($data[$key] ?? null)) {
                app(MediaLibraryService::class)->storeEmbed((string) $data[$key], $baseName);
            }
        }

        return $data;
    }

    /**
     * Video files in the media library, keyed by id.
     *
     * @return array<int, string>
     */
    protected static function videoLibraryOptions(): array
    {
        return Media::query()
            ->whereNull('embed_provider')
            ->where('mime_type', 'like', 'video/%')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Pick the library item's path, or persist an upload to the media library,
     * then strip the picker's helper keys.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected static function resolveVideoField(array $item, string $key, ?string $baseName = null): array
    {
        $source = $item["{$key}_source"] ?? 'upload';

        if ($source === 'library') {
            $media = Media::find($item["{$key}_library"] ?? null);

            $item[$key] = $media?->path ?? ($item[$key] ?? null);
        } elseif (filled($item[$key] ?? null)) {
            app(MediaLibraryService::class)->store(
                path: (string) $item[$key],
                name: $baseName,
                alt: $baseName,
                createOnly: true,
            );
        }

        unset($item["{$key}_source"], $item["{$key}_library"], $item["{$key}_preview"]);

        return $item;
    }
}
