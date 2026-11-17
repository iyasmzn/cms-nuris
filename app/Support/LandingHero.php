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
     * Elemen hero yang bisa diberi CSS kustom → label di panel. Nilainya
     * tersimpan di kunci `{elemen}_css`, mis. `title_css`.
     *
     * @var array<string, string>
     */
    public const CSS_TARGETS = [
        'badge' => 'Label Kecil',
        'title' => 'Judul',
        'highlight' => 'Teks Sorotan',
        'subtitle' => 'Deskripsi',
        'primary' => 'Tombol Utama',
        'secondary' => 'Tombol Kedua',
    ];

    public const CSS_MAX_LENGTH = 500;

    /**
     * @param  list<array{label: string, url: string, primary: bool, external: bool, css: string}>  $buttons
     * @param  array<string, string>  $css  CSS kustom per elemen (kunci dari CSS_TARGETS)
     */
    public function __construct(
        public string $badge,
        public string $title,
        public string $highlight,
        public string $subtitle,
        public array $buttons,
        public string $align,
        public string $size,
        public array $css = [],
    ) {}

    /**
     * Deklarasi CSS kustom yang aman ditaruh di atribut `style`: tanpa
     * kurung kurawal maupun tanda < >, spasi dirapikan, dan dibatasi panjangnya.
     */
    public static function sanitizeCss(mixed $css): string
    {
        if (! is_string($css)) {
            return '';
        }

        $css = str_replace(['{', '}', '<', '>'], '', $css);
        $css = trim((string) preg_replace('/\s+/', ' ', $css));

        return mb_substr($css, 0, self::CSS_MAX_LENGTH);
    }

    /**
     * CSS kustom satu elemen hero dari data mentah `hero`.
     *
     * @param  array<string, mixed>|null  $hero
     */
    public static function customCss(?array $hero, string $target): string
    {
        return self::sanitizeCss($hero["{$target}_css"] ?? null);
    }

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
                    'css' => self::customCss($hero, $key),
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
            css: array_map(
                fn (string $target): string => self::customCss($hero, $target),
                array_combine(array_keys(self::CSS_TARGETS), array_keys(self::CSS_TARGETS)),
            ),
        );
    }
}
