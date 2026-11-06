<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Teks hero ala halaman depan untuk Halaman Statis & Program yang memilih gaya
 * kepala "Hero Landing": label kecil, judul besar, sorotan, deskripsi, dan dua
 * tombol, di atas Cover Hero halaman itu. Nilainya ikut tersimpan di kolom JSON
 * `hero` milik recordnya, bersebelahan dengan pilihan media cover-nya.
 */
final readonly class LandingHero
{
    public const LAYOUT_STANDARD = 'standard';

    public const LAYOUT_LANDING = 'landing';

    /**
     * Gaya kepala halaman → label di panel.
     *
     * @var array<string, string>
     */
    public const LAYOUTS = [
        self::LAYOUT_STANDARD => 'Standar',
        self::LAYOUT_LANDING => 'Hero Landing',
    ];

    /**
     * Perataan isi hero → label di panel.
     *
     * @var array<string, string>
     */
    public const ALIGNMENTS = [
        'left' => 'Kiri',
        'center' => 'Tengah',
        'right' => 'Kanan',
    ];

    /**
     * Tinggi hero → label di panel.
     *
     * @var array<string, string>
     */
    public const SIZES = [
        'md' => 'Sedang — secukupnya mengikuti isi',
        'lg' => 'Tinggi — setinggi hero halaman depan',
        'full' => 'Layar Penuh — memenuhi tinggi layar',
    ];

    /**
     * @param  list<array{label: string, url: string, primary: bool, external: bool}>  $buttons
     */
    public function __construct(
        public string $badge,
        public string $title,
        public string $highlight,
        public string $subtitle,
        public array $buttons,
        public string $align,
        public string $size,
    ) {}

    /**
     * Apakah record memilih kepala halaman "Hero Landing".
     *
     * @param  array<string, mixed>|null  $hero
     */
    public static function isEnabled(?array $hero): bool
    {
        return ($hero['layout'] ?? self::LAYOUT_STANDARD) === self::LAYOUT_LANDING;
    }

    /**
     * Judul yang dikosongkan kembali ke judul record; nilai perataan & tinggi
     * yang asing kembali ke bawaannya. Placeholder {site} dan {year} diisi.
     *
     * @param  array<string, mixed>|null  $hero
     */
    public static function fromArray(?array $hero, string $fallbackTitle): self
    {
        $hero ??= [];
        $fill = fn (mixed $text): string => trim(PpdbLanding::fill(is_string($text) ? $text : ''));

        $buttons = [];

        foreach (['primary', 'secondary'] as $key) {
            $label = $fill($hero["{$key}_label"] ?? '');
            $url = trim((string) ($hero["{$key}_url"] ?? ''));

            if ($label !== '' && $url !== '') {
                $buttons[] = [
                    'label' => $label,
                    'url' => $url,
                    'primary' => $key === 'primary',
                    'external' => Str::startsWith($url, ['http://', 'https://']),
                ];
            }
        }

        $align = (string) ($hero['align'] ?? 'left');
        $size = (string) ($hero['size'] ?? 'lg');
        $title = $fill($hero['title'] ?? '');

        return new self(
            badge: $fill($hero['badge'] ?? ''),
            title: $title !== '' ? $title : $fallbackTitle,
            highlight: $fill($hero['highlight'] ?? ''),
            subtitle: $fill($hero['subtitle'] ?? ''),
            buttons: $buttons,
            align: isset(self::ALIGNMENTS[$align]) ? $align : 'left',
            size: isset(self::SIZES[$size]) ? $size : 'lg',
        );
    }
}
