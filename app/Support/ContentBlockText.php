<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Teks datar yang bisa dipanen dari blok konten. Sejak konten utama halaman dan
 * program disusun sebagai blok, ringkasan meta dan daftar isi tidak lagi bisa
 * dibaca dari satu kolom HTML — keduanya dirangkai dari sini agar SEO tetap
 * mendapat deskripsi yang bermakna.
 */
class ContentBlockText
{
    /**
     * Seluruh teks blok digabung jadi satu paragraf datar, urut sesuai tampilan.
     *
     * @param  array<int, mixed>|null  $blocks
     */
    public static function plain(?array $blocks): string
    {
        $parts = [];

        foreach ($blocks ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $parts[] = $block['eyebrow'] ?? null;
            $parts[] = $block['heading'] ?? null;
            $parts[] = $block['content'] ?? null;
            $parts[] = $block['text'] ?? null;
            $parts[] = $block['caption'] ?? null;

            // Kartu dipanen lewat pemeta yang sama dengan perendernya, jadi
            // kartu yang tidak ikut tampil juga tidak ikut ke meta description.
            foreach (ContentCards::fromItems($block['items'] ?? []) as $card) {
                $parts[] = $card->title;
                $parts[] = $card->description;
            }
        }

        $html = implode(' ', array_filter($parts, fn (mixed $part): bool => is_string($part) && trim($part) !== ''));

        // Tiap tag diganti spasi, bukan dihapus, agar akhir satu paragraf tidak
        // menempel ke awal paragraf berikutnya pada meta description.
        $text = html_entity_decode(strip_tags(preg_replace('/<[^>]*>/', ' ', $html) ?? ''));

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * Ringkasan siap pakai untuk meta description.
     *
     * @param  array<int, mixed>|null  $blocks
     */
    public static function summary(?array $blocks, int $limit = 155): string
    {
        return Str::limit(self::plain($blocks), $limit);
    }

    /**
     * Judul-judul yang bisa dijadikan daftar isi: judul seksi tiap blok dan
     * heading di dalam blok teks.
     *
     * @param  array<int, mixed>|null  $blocks
     * @return list<string>
     */
    public static function headings(?array $blocks): array
    {
        $headings = [];

        foreach ($blocks ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            if (filled($block['heading'] ?? null)) {
                $headings[] = trim(strip_tags((string) $block['heading']));
            }

            // Subjudul di dalam blok teks maupun blok gambar & teks
            foreach ([$block['content'] ?? '', $block['text'] ?? ''] as $html) {
                preg_match_all('/<h[234][^>]*>(.*?)<\/h[234]>/is', (string) $html, $matches);

                foreach ($matches[1] as $match) {
                    $heading = trim(strip_tags($match));

                    if ($heading !== '') {
                        $headings[] = $heading;
                    }
                }
            }
        }

        return array_values(array_unique($headings));
    }
}
