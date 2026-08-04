<?php

namespace App\Models;

use Database\Factories\AlumniStatFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniStat extends Model
{
    /** @use HasFactory<AlumniStatFactory> */
    use HasFactory;

    protected $fillable = ['icon', 'icon_image', 'label', 'value', 'sub', 'sort_order'];

    /**
     * @return Builder<static>
     */
    public static function ordered(): Builder
    {
        return static::orderBy('sort_order')->orderBy('id');
    }
}
