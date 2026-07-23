<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;

class StaticPageSeeder extends Seeder
{
    /**
     * Seed halaman-halaman statis default.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Profil Nurul Islam Tengaran',
                'slug' => 'informasi-umum',
                'meta_description' => 'Profil lembaga pendidikan Islam Nurul Islam Tengaran di bawah Yayasan Pendidikan Islam Sabilul Khoirot.',
                'content' => '<h2>Tentang Nurul Islam Tengaran</h2><p>Nurul Islam Tengaran adalah lembaga pendidikan Islam yang bernaung di bawah Yayasan Pendidikan Islam Sabilul Khoirot, berlokasi di Kecamatan Tengaran, Kabupaten Semarang, Jawa Tengah.</p><p>Berawal dari Pondok Pesantren Sabilul Khoirot yang berdiri pada tahun 1973, lembaga ini terus berkembang menyelenggarakan pendidikan dari jenjang KB/TKIT, SDIT, SMPIT, SMAIT, Madrasah Aliyah, hingga Pondok Pesantren.</p><p>Dengan motto <strong>"Mendidik Generasi Beradab, Disiplin, Kreatif, dan Cerdas"</strong>, Nurul Islam Tengaran berkomitmen membentuk generasi Islami yang berakhlak mulia, mandiri, dan berdaya saing.</p>',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Sejarah',
                'slug' => 'sejarah',
                'meta_description' => 'Sejarah berdiri dan perkembangan Nurul Islam Tengaran sejak 1973.',
                'content' => '<h2>Sejarah Nurul Islam Tengaran</h2><p>Cikal bakal Nurul Islam Tengaran bermula pada tahun 1973 dengan berdirinya <strong>Pondok Pesantren Sabilul Khoirot</strong> yang dirintis oleh KH Zainal Mahmud.</p><p>Seiring berjalannya waktu, lembaga ini berkembang pesat dan membuka berbagai jenjang pendidikan formal:</p><ul><li><strong>1999</strong> — Berdirinya TKIT Nurul Islam</li><li><strong>2001</strong> — Berdirinya SDIT Nurul Islam</li><li><strong>2007</strong> — Berdirinya SMPIT Nurul Islam</li><li><strong>2013</strong> — Berdirinya Madrasah Aliyah (MA) Nurul Islam</li><li><strong>2020</strong> — Berdirinya SMAIT Nurul Islam</li></ul><p>Kini seluruh unit pendidikan tersebut dikelola secara terpadu di bawah <strong>Yayasan Pendidikan Islam Sabilul Khoirot</strong>.</p>',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Visi & Misi',
                'slug' => 'visi-misi',
                'meta_description' => 'Visi dan misi Nurul Islam Tengaran sebagai lembaga penyelenggara pendidikan Islam.',
                'content' => '<h2>Visi</h2><p>Menjadi lembaga penyelenggara pendidikan Islam terdepan di Jawa Tengah.</p><h2>Misi</h2><ul><li>Menyelenggarakan lembaga pendidikan yang profesional dan berlandaskan nilai-nilai Islam.</li><li>Mengembangkan peserta didik yang cerdas, mandiri, berjiwa kepemimpinan, dan berkarakter kuat.</li><li>Menjadi rujukan dalam pengembangan sumber daya manusia di bidang pendidikan.</li><li>Menyelenggarakan unit usaha produktif yang mendukung misi pendidikan.</li><li>Membangun jaringan pendidikan pada tingkat regional, nasional, dan global.</li><li>Mewujudkan lembaga yang bermanfaat bagi masyarakat.</li></ul>',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Informasi Lokasi',
                'slug' => 'informasi-lokasi',
                'meta_description' => 'Alamat, peta lokasi, dan kontak Nurul Islam Tengaran.',
                'content' => '<h2>Lokasi</h2><p>Nurul Islam Tengaran berlokasi di jalur utama Salatiga – Solo, mudah dijangkau dengan kendaraan umum maupun pribadi.</p><h3>Alamat Lengkap</h3><p>Jl. Raya Salatiga – Solo KM 8, Butuh, RT 20 RW 11, Kecamatan Tengaran, Kabupaten Semarang, Jawa Tengah.</p><h3>Kontak</h3><ul><li><strong>Telepon:</strong> (0298) 3429395</li><li><strong>Email:</strong> ypisabkho@gmail.com</li><li><strong>Instagram:</strong> @nurulislam_tengaran</li></ul><h3>Jam Operasional</h3><ul><li>Senin – Sabtu: 07.00 – 15.00 WIB</li><li>Ahad: Libur</li></ul>',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($pages as $data) {
            StaticPage::firstOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
