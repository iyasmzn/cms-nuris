<?php

namespace App\Models;

use Database\Factories\AlumniUniversityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniUniversity extends Model
{
    /** @use HasFactory<AlumniUniversityFactory> */
    use HasFactory;

    protected $fillable = ['name', 'logo', 'url', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * URL logo kampus — null bila belum diunggah, sehingga tampilan publik
     * bisa memakai inisial nama sebagai gantinya.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }

    /**
     * Inisial nama kampus, dipakai saat logo belum tersedia.
     */
    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim((string) $this->name)) ?: [])
            ->filter()
            ->take(3)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    /**
     * @return Builder<static>
     */
    public static function active(): Builder
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
