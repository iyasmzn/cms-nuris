<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Mengubah daftar kartu mentah menjadi objek siap render untuk komponen
 * <x-content-card>. Dipakai bersama oleh seksi dinamis halaman depan
 * (kolom `items`) dan blok kartu pada "Konten Tambahan".
 */
class ContentCards
{
    /**
     * Kartu yang siap dirender — hanya yang punya judul atau gambar, dengan URL
     * gambar sudah diselesaikan dan tautannya dinormalkan.
     *
     * Kartu bertombol (label + tautan terisi) menampilkan tombol CTA; kartu yang
     * hanya punya tautan tanpa label dibuat bisa diklik seluruh kartunya.
     *
     * @return Collection<int, object{image_url: ?string, title: ?string, description: ?string, cta_label: ?string, cta_url: ?string, cta_new_tab: bool, has_cta: bool, is_clickable: bool}>
     */
    public static function fromItems(mixed $items): Collection
    {
        return collect(is_array($items) ? $items : [])
            ->filter(fn (mixed $item): bool => is_array($item)
                && (filled($item['title'] ?? null) || filled($item['image'] ?? null)))
            ->map(function (array $item): object {
                $label = $item['cta_label'] ?? null;
                $url = $item['cta_url'] ?? null;

                return (object) [
                    'image_url' => icon_url($item['image'] ?? null),
                    'title' => $item['title'] ?? null,
                    'description' => $item['description'] ?? null,
                    'cta_label' => $label,
                    'cta_url' => $url,
                    'cta_new_tab' => (bool) ($item['cta_new_tab'] ?? false),
                    'has_cta' => filled($label) && filled($url),
                    'is_clickable' => blank($label) && filled($url),
                ];
            })
            ->values();
    }
}
