<?php

namespace App\Models;

use App\Support\ContentCards;
use App\Support\SectionTypography;
use Database\Factories\ContentSectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Seksi bebas di halaman depan. Isinya bisa berupa satu gambar berdampingan
 * dengan teks, deretan kartu, atau kartu yang berjalan dalam carousel — semua
 * di atas latar yang bisa diatur. Urutannya diatur bersama seksi bawaan lewat
 * Pengaturan Halaman Depan.
 */
class ContentSection extends Model
{
    /** @use HasFactory<ContentSectionFactory> */
    use HasFactory;

    /**
     * Prefix kunci seksi ini di dalam setting `section_order`.
     */
    public const ORDER_KEY_PREFIX = 'content_section_';

    /**
     * Bentuk isi seksi.
     *
     * @var array<string, string>
     */
    public const LAYOUTS = [
        'media' => 'Gambar & Teks',
        'cards' => 'Deretan Kartu',
        'carousel' => 'Carousel Kartu',
    ];

    /**
     * Jumlah kartu sebaris (pada layar lebar) → label.
     *
     * @var array<int, string>
     */
    public const ITEM_COLUMNS = [
        1 => '1 kartu',
        2 => '2 kartu',
        3 => '3 kartu',
        4 => '4 kartu',
    ];

    /**
     * Perbandingan sisi gambar kartu → label. Kuncinya dipakai apa adanya di
     * basis data maupun di JSON blok, lalu diterjemahkan ke nilai CSS oleh
     * `cardRatioCss()`.
     *
     * @var array<string, string>
     */
    public const CARD_RATIOS = [
        '4-3' => 'Lanskap 4:3',
        '16-9' => 'Lebar 16:9',
        '3-2' => 'Lanskap 3:2',
        '1-1' => 'Persegi 1:1',
        '3-4' => 'Potret 3:4',
    ];

    public const DEFAULT_CARD_RATIO = '4-3';

    /**
     * Jeda perpindahan carousel yang bisa dipilih admin (detik).
     */
    public const AUTOPLAY_MIN_DELAY = 2;

    public const AUTOPLAY_MAX_DELAY = 15;

    /** @var array<string, string> */
    public const IMAGE_POSITIONS = [
        'left' => 'Gambar di Kiri',
        'right' => 'Gambar di Kanan',
    ];

    /**
     * Latar seksi. `default` memakai token `--bg` (#f5f5f7, abu lembut) seperti
     * seksi bawaan lain, `alt` memakai `--bg-alt` (#ffffff, putih bersih).
     *
     * @var array<string, string>
     */
    public const BACKGROUNDS = [
        'default' => 'Abu Lembut',
        'alt' => 'Putih Bersih',
        'image' => 'Gambar',
    ];

    /**
     * Radius blur latar (px) → label.
     *
     * @var array<int, string>
     */
    public const BLUR_LEVELS = [
        0 => 'Tanpa Blur',
        4 => 'Ringan',
        8 => 'Sedang',
        16 => 'Kuat',
    ];

    /**
     * Kepekatan lapisan gelap (persen) → label.
     *
     * @var array<int, string>
     */
    public const OVERLAY_LEVELS = [
        0 => 'Tanpa Lapisan Gelap',
        25 => 'Sedikit Gelap',
        45 => 'Sedang',
        65 => 'Gelap',
        80 => 'Sangat Gelap',
    ];

    /**
     * Pola SVG yang bisa dilukis di atas latar polos. Gambarnya sendiri
     * disimpan di `App\Support\SectionPatterns`; di sini hanya daftar pilihan
     * yang tampil di panel.
     *
     * @var array<string, string>
     */
    public const BACKGROUND_PATTERNS = [
        'none' => 'Tanpa Pola',
        'dots' => 'Titik-Titik',
        'crosses' => 'Palang Kecil',
        'circles' => 'Lingkaran',
        'grid' => 'Garis Kotak',
        'diagonal' => 'Garis Miring',
        'weave' => 'Anyaman',
        'diamonds' => 'Belah Ketupat',
        'triangles' => 'Segitiga',
        'zigzag' => 'Zigzag',
        'waves' => 'Gelombang',
        'scales' => 'Sisik',
        'arabesque' => 'Ornamen Islami',
        'khatam' => 'Bintang Khatam',
    ];

    /**
     * Kepekatan pola (persen) → label. Sengaja bertingkat halus: pola latar
     * bertugas menghias, bukan merebut perhatian dari isi seksi.
     *
     * @var array<int, string>
     */
    public const PATTERN_OPACITY_LEVELS = [
        4 => 'Samar',
        8 => 'Halus',
        14 => 'Sedang',
        22 => 'Tegas',
        32 => 'Kuat',
    ];

    public const DEFAULT_PATTERN_OPACITY = 8;

    /**
     * Ukuran ubin pola sebagai persentase ukuran aslinya → label. Makin kecil
     * persennya, makin rapat polanya berulang.
     *
     * @var array<int, string>
     */
    public const PATTERN_SCALES = [
        50 => 'Sangat Rapat',
        75 => 'Rapat',
        100 => 'Sedang',
        150 => 'Besar',
        200 => 'Sangat Besar',
    ];

    public const DEFAULT_PATTERN_SCALE = 100;

    /**
     * Warna pola. `primary` dan `text` mengikuti token tema, jadi ikut berubah
     * saat admin mengganti warna utama situs; `custom` memakai hex sendiri.
     *
     * @var array<string, string>
     */
    public const PATTERN_COLORS = [
        'primary' => 'Warna Utama Tema',
        'text' => 'Warna Teks (gelap)',
        'muted' => 'Abu Lembut',
        'white' => 'Putih',
        'custom' => 'Warna Sendiri',
    ];

    public const DEFAULT_PATTERN_COLOR = 'primary';

    /**
     * Cara pola bergerak saat animasinya dinyalakan. `scroll` tidak berjalan
     * sendiri: polanya bergeser mengikuti posisi guliran halaman.
     *
     * @var array<string, string>
     */
    public const PATTERN_MOTIONS = [
        'drift' => 'Hanyut Diagonal',
        'drift_x' => 'Hanyut Mendatar',
        'pulse' => 'Denyut Halus',
        'scroll' => 'Ikut Guliran',
    ];

    public const DEFAULT_PATTERN_MOTION = 'drift';

    /**
     * Laju gerak pola dalam piksel per detik → label.
     *
     * Sengaja piksel/detik, bukan lama satu putaran: satu putaran hanya sejauh
     * satu ubin (16–60px), sehingga "sekian detik per putaran" menghasilkan
     * kecepatan yang jauh berbeda antar pola — dan pada ubin kecil pergerakannya
     * jadi terlalu pelan untuk terlihat sama sekali.
     *
     * @var array<int, string>
     */
    public const PATTERN_SPEEDS = [
        3 => 'Sangat Lambat',
        6 => 'Lambat',
        12 => 'Sedang',
        20 => 'Cepat',
    ];

    public const DEFAULT_PATTERN_SPEED = 6;

    /**
     * Cara gambar latar bereaksi terhadap guliran halaman.
     *
     * @var array<string, string>
     */
    public const PARALLAX_MODES = [
        'none' => 'Ikut Menggulir',
        'scroll' => 'Bergeser (Parallax)',
        'fixed' => 'Diam (Terkunci ke Layar)',
    ];

    /**
     * Batas kekuatan parallax yang bisa dipilih admin lewat slider (persen).
     */
    public const PARALLAX_MIN_SPEED = 1;

    public const PARALLAX_MAX_SPEED = 100;

    protected $fillable = [
        'eyebrow',
        'title',
        'description',
        'typography',
        'layout',
        'image',
        'image_position',
        'items',
        'items_columns',
        'items_ratio',
        'carousel_autoplay',
        'carousel_autoplay_delay',
        'carousel_pause_on_hover',
        'carousel_loop',
        'carousel_arrows',
        'carousel_dots',
        'background',
        'background_image',
        'background_pattern',
        'background_pattern_opacity',
        'background_pattern_scale',
        'background_pattern_color',
        'background_pattern_custom_color',
        'background_pattern_animated',
        'background_pattern_motion',
        'background_pattern_speed',
        'background_blur',
        'background_overlay',
        'background_parallax_mode',
        'background_parallax_speed',
        'background_light_text',
        'anchor',
        'cta_label',
        'cta_url',
        'cta_new_tab',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'typography' => 'array',
            'items' => 'array',
            'items_columns' => 'integer',
            'carousel_autoplay' => 'boolean',
            'carousel_autoplay_delay' => 'integer',
            'carousel_pause_on_hover' => 'boolean',
            'carousel_loop' => 'boolean',
            'carousel_arrows' => 'boolean',
            'carousel_dots' => 'boolean',
            'background_pattern_opacity' => 'integer',
            'background_pattern_scale' => 'integer',
            'background_pattern_animated' => 'boolean',
            'background_pattern_speed' => 'integer',
            'background_blur' => 'integer',
            'background_overlay' => 'integer',
            'background_parallax_speed' => 'integer',
            'background_light_text' => 'boolean',
            'cta_new_tab' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Kunci seksi ini pada daftar urutan halaman depan.
     */
    public function getOrderKeyAttribute(): string
    {
        return self::ORDER_KEY_PREFIX.$this->id;
    }

    /**
     * ID anchor yang aman dipakai sebagai target tautan menu.
     */
    public function getAnchorIdAttribute(): string
    {
        return Str::slug($this->anchor ?: 'seksi-'.$this->id);
    }

    /**
     * Gaya huruf tiap elemen teks seksi ini.
     */
    public function getTypographyStylesAttribute(): SectionTypography
    {
        return SectionTypography::fromArray($this->typography);
    }

    /**
     * Deskripsi kini opsional — seksi kartu sering cukup dengan judulnya saja.
     */
    public function getHasDescriptionAttribute(): bool
    {
        return filled(trim(strip_tags((string) $this->description)));
    }

    /**
     * URL gambar dari storage, atau null bila seksi tampil tanpa gambar.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    /**
     * Seksi menampilkan satu gambar berdampingan teks.
     */
    public function getShowsMediaAttribute(): bool
    {
        return $this->layout === 'media';
    }

    /**
     * Kartu yang siap dirender, dibentuk oleh pemeta yang sama dengan blok
     * kartu pada "Konten Tambahan".
     *
     * @return Collection<int, object{image_url: ?string, title: ?string, description: ?string, cta_label: ?string, cta_url: ?string, cta_new_tab: bool, has_cta: bool, is_clickable: bool}>
     */
    public function getCardsAttribute(): Collection
    {
        return ContentCards::fromItems($this->items);
    }

    /**
     * Tata letak kartu hanya berlaku bila kartunya memang ada.
     */
    public function getUsesCardsAttribute(): bool
    {
        return in_array($this->layout, ['cards', 'carousel'], true) && $this->cards->isNotEmpty();
    }

    public function getUsesCarouselAttribute(): bool
    {
        return $this->layout === 'carousel' && $this->cards->isNotEmpty();
    }

    /**
     * Jumlah kartu sebaris di layar lebar (1–4).
     */
    public function getCardColumnsAttribute(): int
    {
        $columns = (int) $this->items_columns;

        return max(1, min(4, $columns ?: 3));
    }

    /**
     * Carousel berjalan sendiri hanya bila modenya carousel dan saklarnya nyala.
     */
    public function getUsesAutoplayAttribute(): bool
    {
        return $this->uses_carousel && (bool) $this->carousel_autoplay;
    }

    /**
     * Perbandingan sisi gambar kartu sebagai nilai CSS `aspect-ratio`. Nilai
     * yang tidak dikenal — termasuk milik blok lama yang belum punya setelan
     * ini — jatuh ke bawaan 4:3 supaya tampilannya tidak berubah.
     */
    public static function cardRatioCss(?string $ratio): string
    {
        $ratio = array_key_exists((string) $ratio, self::CARD_RATIOS) ? (string) $ratio : self::DEFAULT_CARD_RATIO;

        return str_replace('-', ' / ', $ratio);
    }

    /**
     * Perbandingan sisi gambar kartu milik seksi ini, siap dipakai di CSS.
     */
    public function getCardMediaRatioAttribute(): string
    {
        return self::cardRatioCss($this->items_ratio);
    }

    /**
     * Apakah carousel yang berjalan sendiri ikut berhenti saat disorot kursor.
     * Hanya berarti bila autoplay-nya memang menyala.
     */
    public function getPausesOnHoverAttribute(): bool
    {
        return $this->uses_autoplay && (bool) ($this->carousel_pause_on_hover ?? true);
    }

    /**
     * Jeda perpindahan carousel dalam milidetik, dibatasi ke rentang yang dipilih admin.
     */
    public function getAutoplayDelayMsAttribute(): int
    {
        $seconds = (int) ($this->carousel_autoplay_delay ?: 5);

        return min(self::AUTOPLAY_MAX_DELAY, max(self::AUTOPLAY_MIN_DELAY, $seconds)) * 1000;
    }

    /**
     * Latar gambar hanya aktif bila modenya dipilih dan gambarnya terisi.
     */
    public function getHasBackgroundImageAttribute(): bool
    {
        return $this->background === 'image' && filled($this->background_image);
    }

    /**
     * URL gambar latar dari storage, atau null bila seksi tidak memakainya.
     */
    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->has_background_image ? asset('storage/'.$this->background_image) : null;
    }

    /**
     * Kepekatan lapisan gelap sebagai nilai opacity CSS (0–1).
     */
    public function getOverlayOpacityAttribute(): float
    {
        return round(min(100, max(0, (int) $this->background_overlay)) / 100, 2);
    }

    /**
     * Intensitas parallax sebagai pengali pergeseran (0–1).
     */
    public function getParallaxFactorAttribute(): float
    {
        $speed = min(self::PARALLAX_MAX_SPEED, max(0, (int) $this->background_parallax_speed));

        return round($speed / 100, 2);
    }

    /**
     * Efek gulir hanya berlaku bila seksi benar-benar punya latar gambar.
     */
    public function getUsesScrollParallaxAttribute(): bool
    {
        return $this->has_background_image && $this->background_parallax_mode === 'scroll';
    }

    /**
     * Latar terkunci ke layar; kontennya yang meluncur di atasnya.
     */
    public function getUsesFixedBackgroundAttribute(): bool
    {
        return $this->has_background_image && $this->background_parallax_mode === 'fixed';
    }

    /**
     * Teks putih dipakai hanya di atas latar gambar.
     */
    public function getUsesLightTextAttribute(): bool
    {
        return $this->has_background_image && $this->background_light_text;
    }

    /**
     * Tombol CTA hanya dirender bila label dan tujuannya terisi.
     */
    public function getHasCtaAttribute(): bool
    {
        return filled($this->cta_label) && filled($this->cta_url);
    }

    /**
     * Ambil id dari kunci urutan, misal `content_section_7` → 7.
     */
    public static function idFromOrderKey(string $key): ?int
    {
        if (! str_starts_with($key, self::ORDER_KEY_PREFIX)) {
            return null;
        }

        $id = substr($key, strlen(self::ORDER_KEY_PREFIX));

        return ctype_digit($id) ? (int) $id : null;
    }

    /**
     * @return Builder<static>
     */
    public static function published(): Builder
    {
        return static::where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return Builder<static>
     */
    public static function ordered(): Builder
    {
        return static::orderBy('sort_order')->orderBy('id');
    }
}
