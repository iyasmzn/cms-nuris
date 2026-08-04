<?php

namespace Database\Seeders;

use App\Models\ContentSection;
use Illuminate\Database\Seeder;

class ContentSectionSeeder extends Seeder
{
    public function run(): void
    {
        if (ContentSection::exists()) {
            return;
        }

        $sections = [
            [
                'eyebrow' => 'Tentang Kami',
                'title' => 'Lingkungan Belajar yang Nyaman dan Islami',
                'description' => '<p>Kami memadukan kurikulum nasional dengan pendidikan diniyah agar santri tumbuh cerdas secara akademik sekaligus kokoh secara akhlak.</p><ul><li>Pembinaan karakter setiap hari</li><li>Bimbingan intensif menjelang ujian</li><li>Lingkungan asrama yang tertib dan aman</li></ul>',
                'image_position' => 'right',
                'background' => 'default',
                'anchor' => 'tentang-kami',
                'cta_label' => 'Kenali Kami Lebih Dekat',
                'cta_url' => '/#profil',
                'sort_order' => 0,
            ],
            [
                'eyebrow' => 'Fasilitas',
                'title' => 'Sarana Penunjang yang Lengkap',
                'description' => '<p>Masjid, laboratorium, perpustakaan, hingga sarana olahraga disiapkan untuk mendukung kegiatan belajar dan pengembangan minat bakat santri.</p>',
                'image_position' => 'left',
                'background' => 'alt',
                'anchor' => 'fasilitas',
                'cta_label' => 'Lihat Galeri',
                'cta_url' => '/galeri',
                'sort_order' => 1,
            ],
        ];

        foreach ($sections as $section) {
            ContentSection::create($section + ['is_published' => true]);
        }
    }
}
