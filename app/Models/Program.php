<?php

namespace App\Models;

use App\Support\ContentBlockText;
use App\Support\PageHero;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'image', 'blocks', 'hero', 'show_sidebar',
        'icon', 'icon_image', 'category', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'blocks' => 'array',
        'hero' => 'array',
        'is_published' => 'boolean',
        'show_sidebar' => 'boolean',
    ];

    // ── Scopes ──────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    // ── Computed Attributes ──────────────────────────────────

    /**
     * Cover hero program: gambar, berkas video, atau video YouTube. Bila
     * gambarnya tidak diisi, hero tetap memakai gambar program.
     */
    public function getHeroCoverAttribute(): PageHero
    {
        return PageHero::fromArray($this->hero);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : "https://picsum.photos/seed/program-{$this->id}/800/500";
    }

    /**
     * Ringkasan untuk meta description. Sejak konten detail disusun sebagai
     * seksi, teksnya dipanen dari blok bila ringkasannya kosong.
     */
    public function getMetaDescriptionAttribute(): string
    {
        $source = $this->excerpt ?: ContentBlockText::plain($this->blocks);

        return Str::limit($source, 155);
    }

    /**
     * Judul seksi & heading di dalamnya, untuk daftar isi di sidebar.
     *
     * @return list<string>
     */
    public function getContentHeadingsAttribute(): array
    {
        return ContentBlockText::headings($this->blocks);
    }
}
