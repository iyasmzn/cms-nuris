<?php

namespace App\Models;

use App\Support\PageTargets;
use Database\Factories\FloatingButtonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FloatingButton extends Model
{
    /** @use HasFactory<FloatingButtonFactory> */
    use HasFactory;

    public const DISPLAY_ALL = 'all';

    public const DISPLAY_ONLY = 'only';

    public const DISPLAY_EXCEPT = 'except';

    protected $fillable = [
        'label', 'url', 'icon', 'icon_image', 'color',
        'open_in_new_tab', 'is_active', 'sort_order',
        'display_mode', 'display_targets',
    ];

    protected $attributes = [
        'display_mode' => self::DISPLAY_ALL,
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'display_targets' => 'array',
    ];

    protected static function booted(): void
    {
        // Sasaran tidak berarti apa-apa di mode "semua halaman"; dikosongkan
        // supaya tabel admin tidak menampilkan sisa pilihan lama.
        static::saving(function (self $button): void {
            if ($button->display_mode === self::DISPLAY_ALL) {
                $button->display_targets = null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function displayModeOptions(): array
    {
        return [
            self::DISPLAY_ALL => 'Semua halaman',
            self::DISPLAY_ONLY => 'Hanya di halaman tertentu',
            self::DISPLAY_EXCEPT => 'Semua halaman, kecuali',
        ];
    }

    public static function active(): Builder
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Active buttons that belong on the page being requested.
     *
     * @return Collection<int, self>
     */
    public static function forRequest(Request $request): Collection
    {
        $buttons = static::active()->get();

        // Most sites never target pages; skip resolving the page entirely then.
        if ($buttons->every(fn (self $button): bool => $button->display_mode === self::DISPLAY_ALL)) {
            return $buttons;
        }

        $pageTargets = PageTargets::forRequest($request);

        return $buttons
            ->filter(fn (self $button): bool => $button->isVisibleOn($pageTargets))
            ->values();
    }

    /**
     * Whether this button shows on a page matching the given targets.
     *
     * @param  array<int, string>  $pageTargets  Targets of the current page, from {@see PageTargets::forRequest()}.
     */
    public function isVisibleOn(array $pageTargets): bool
    {
        $matchesPage = array_intersect($this->display_targets ?? [], $pageTargets) !== [];

        return match ($this->display_mode) {
            self::DISPLAY_ONLY => $matchesPage,
            self::DISPLAY_EXCEPT => ! $matchesPage,
            default => true,
        };
    }

    /**
     * Readable summary of where the button shows, for the admin table.
     */
    public function displaySummary(): string
    {
        if ($this->display_mode === self::DISPLAY_ALL) {
            return 'Semua halaman';
        }

        $labels = PageTargets::labels();
        $targets = collect($this->display_targets ?? [])
            ->map(fn (string $target): string => $labels[$target] ?? 'Halaman terhapus')
            ->implode(', ');

        return $this->display_mode === self::DISPLAY_ONLY
            ? "Hanya: {$targets}"
            : "Kecuali: {$targets}";
    }
}
