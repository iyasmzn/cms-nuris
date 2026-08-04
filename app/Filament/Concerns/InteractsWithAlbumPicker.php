<?php

namespace App\Filament\Concerns;

use App\Models\Album;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rules\Unique;

/**
 * Album chooser used wherever media can be grouped: type to search existing
 * albums, or create one inline. Because albums are real records, picking one
 * can never introduce a typo or a near-duplicate — and renaming an album later
 * updates every media item at once.
 *
 * Bound to the plain `album_id` column rather than the relationship so the same
 * field works inside modal actions, which have no record to relate to.
 */
trait InteractsWithAlbumPicker
{
    protected static function albumPicker(string $key = 'album_id'): Select
    {
        return Select::make($key)
            ->label('Album')
            ->options(fn (): array => Album::options())
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder('Tanpa album')
            ->createOptionForm(static::albumFormSchema())
            ->createOptionModalHeading('Album Baru')
            // An album typed with different casing reuses the existing one.
            ->createOptionUsing(fn (array $data): int => Album::findByName($data['name'])?->getKey()
                ?? Album::create([
                    'name' => trim($data['name']),
                    'description' => $data['description'] ?? null,
                ])->getKey())
            ->editOptionForm(static::albumFormSchema())
            ->editOptionModalHeading('Ubah Album')
            // Neither callback is passed the selected value, so it is read back
            // off the component itself.
            ->fillEditOptionActionFormUsing(fn (Select $component): array => Album::find($component->getState())
                ?->only(['id', 'name', 'description']) ?? [])
            ->updateOptionUsing(function (array $data, Select $component): void {
                Album::find($data['id'] ?? $component->getState())?->update([
                    'name' => trim($data['name']),
                    'description' => $data['description'] ?? null,
                ]);
            });
    }

    /**
     * Fields shared by the inline create & edit album modals.
     *
     * The album being edited is carried in a hidden `id` so uniqueness can
     * ignore it. `ignoreRecord` must not be used here: these modals live inside
     * a media form, so the "current record" is the media item, not the album.
     *
     * @return array<int, Hidden|TextInput|Textarea>
     */
    protected static function albumFormSchema(): array
    {
        return [
            Hidden::make('id'),

            TextInput::make('name')
                ->label('Nama Album')
                ->required()
                ->maxLength(100)
                ->placeholder('Contoh: Wisuda 2025, Fasilitas')
                ->unique(
                    table: Album::class,
                    column: 'name',
                    ignoreRecord: false,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->ignore($get('id')),
                )
                ->validationMessages(['unique' => 'Album dengan nama ini sudah ada — pilih saja dari daftar.']),

            Textarea::make('description')
                ->label('Deskripsi')
                ->rows(2)
                ->maxLength(500)
                ->placeholder('Keterangan singkat tentang album ini…'),
        ];
    }
}
