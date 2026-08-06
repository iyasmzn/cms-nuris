<?php

namespace App\Support;

/**
 * Gaya huruf tiap elemen teks pada seksi dinamis halaman depan.
 *
 * Nilainya disimpan sebagai satu kolom JSON pada record seksi dengan bentuk
 * `['title' => ['align' => 'center', 'weight' => 700, 'size' => 'lg', 'color' => '#123456'], …]`.
 * Setiap bagian boleh kosong: yang kosong berarti elemen itu memakai gaya
 * bawaan rancangan seksi, sehingga seksi lama tetap tampil persis seperti
 * sebelum fitur ini ada.
 */
class SectionTypography
{
    /**
     * Elemen teks yang gayanya bisa diatur → labelnya di panel.
     *
     * @var array<string, string>
     */
    public const ELEMENTS = [
        'eyebrow' => 'Label Kecil',
        'title' => 'Judul',
        'description' => 'Deskripsi',
        'card_title' => 'Judul Kartu',
        'card_description' => 'Deskripsi Kartu',
    ];

    /**
     * Elemen yang hanya relevan pada tata letak berkartu.
     *
     * @var list<string>
     */
    public const CARD_ELEMENTS = ['card_title', 'card_description'];

    /** @var array<string, string> */
    public const ALIGNMENTS = [
        'left' => 'Kiri',
        'center' => 'Tengah',
        'right' => 'Kanan',
        'justify' => 'Rata Kiri-Kanan',
    ];

    /** @var array<int, string> */
    public const WEIGHTS = [
        400 => 'Normal',
        500 => 'Medium',
        600 => 'Agak Tebal',
        700 => 'Tebal',
        800 => 'Sangat Tebal',
    ];

    /** @var array<string, string> */
    public const SIZES = [
        'sm' => 'Kecil',
        'md' => 'Normal',
        'lg' => 'Besar',
        'xl' => 'Sangat Besar',
    ];

    /**
     * Pengali ukuran huruf terhadap ukuran bawaan elemennya. Dipakai sebagai
     * custom property `--cs-scale`, jadi ukuran responsif tiap elemen tetap
     * dipegang CSS-nya sendiri.
     *
     * @var array<string, float>
     */
    private const SIZE_SCALES = [
        'sm' => 0.875,
        'md' => 1.0,
        'lg' => 1.15,
        'xl' => 1.3,
    ];

    /**
     * @param  array<string, mixed>  $settings  isi kolom `typography` apa adanya
     */
    public function __construct(private readonly array $settings = []) {}

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public static function fromArray(?array $settings): self
    {
        return new self($settings ?? []);
    }

    /**
     * Deklarasi CSS siap tempel pada atribut `style` elemen, atau string kosong
     * bila elemennya memakai gaya bawaan seluruhnya.
     */
    public function styleFor(string $element): string
    {
        $declarations = array_filter([
            $this->alignFor($element) ? 'text-align:'.$this->alignFor($element) : null,
            $this->weightFor($element) ? 'font-weight:'.$this->weightFor($element) : null,
            $this->scaleFor($element) !== 1.0 ? '--cs-scale:'.$this->scaleFor($element) : null,
            $this->colorFor($element) ? 'color:'.$this->colorFor($element) : null,
        ]);

        return implode(';', $declarations);
    }

    /**
     * Perataan pilihan admin, atau null bila elemennya ikut tata letak seksi.
     */
    public function alignFor(string $element): ?string
    {
        $align = $this->value($element, 'align');

        return array_key_exists((string) $align, self::ALIGNMENTS) ? (string) $align : null;
    }

    /**
     * Ketebalan pilihan admin, atau null bila memakai bawaan elemennya.
     */
    public function weightFor(string $element): ?int
    {
        $weight = (int) $this->value($element, 'weight');

        return array_key_exists($weight, self::WEIGHTS) ? $weight : null;
    }

    public function scaleFor(string $element): float
    {
        return self::SIZE_SCALES[(string) $this->value($element, 'size')] ?? 1.0;
    }

    /**
     * Warna teks pilihan admin. Hanya heksadesimal yang diterima supaya nilai
     * dari basis data tidak bisa menyelundupkan deklarasi CSS lain.
     */
    public function colorFor(string $element): ?string
    {
        $color = $this->value($element, 'color');

        return is_string($color) && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) === 1
            ? $color
            : null;
    }

    /**
     * Elemen dengan warna kustom perlu ditandai agar teks tebal dan tautan di
     * dalam deskripsi ikut warnanya, bukan tetap memakai token tema.
     */
    public function isColored(string $element): bool
    {
        return $this->colorFor($element) !== null;
    }

    private function value(string $element, string $key): mixed
    {
        $element = $this->settings[$element] ?? null;

        return is_array($element) ? ($element[$key] ?? null) : null;
    }
}
