<?php

namespace App\Models;

use Database\Factories\StatFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    /** @use HasFactory<StatFactory> */
    use HasFactory;

    protected $fillable = ['icon', 'icon_image', 'label', 'value', 'sub', 'url', 'url_new_tab', 'sort_order'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'url_new_tab' => 'boolean',
        ];
    }

    /**
     * Kartu hanya dijadikan tautan bila tujuannya terisi. Alamatnya boleh path
     * internal (`/prestasi`, `#profil`) maupun URL situs lain.
     */
    public function getHasLinkAttribute(): bool
    {
        return filled($this->url);
    }

    /**
     * Tab baru mengikuti saklar yang dipilih admin, bukan ditebak dari alamatnya.
     */
    public function getLinkOpensInNewTabAttribute(): bool
    {
        return $this->has_link && $this->url_new_tab;
    }

    /**
     * @return Builder<static>
     */
    public static function ordered(): Builder
    {
        return static::orderBy('sort_order')->orderBy('id');
    }
}
