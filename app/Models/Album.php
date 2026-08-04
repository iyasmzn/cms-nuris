<?php

namespace App\Models;

use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Album names keyed by id, alphabetically — used by pickers & filters.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return static::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Find an album by name regardless of casing, so "Wisuda 2025" and
     * "wisuda 2025" never end up as two albums.
     */
    public static function findByName(string $name): ?self
    {
        return static::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();
    }
}
