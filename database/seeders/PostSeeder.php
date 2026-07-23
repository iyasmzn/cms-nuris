<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        if (Post::exists()) {
            return;
        }

        $posts = [
            [
                'title' => 'Keterbukaan Informasi Pengelolaan Dana BOSP SMAIT Nurul Islam Tengaran Tahap 1 Tahun 2025',
                'slug' => 'keterbukaan-informasi-bosp-smait-tahap-1-2025',
                'excerpt' => 'Di tengah dinamika pengelolaan dana pendidikan, transparansi menjadi kunci utama dalam menciptakan kepercayaan dan akuntabilitas.',
                'content' => '<p>Di tengah dinamika pengelolaan dana pendidikan, transparansi menjadi kunci utama dalam menciptakan kepercayaan dan akuntabilitas.</p><p>Sebagai wujud komitmen terhadap keterbukaan informasi publik, SMA Islam Terpadu Nurul Islam Tengaran menyampaikan laporan pengelolaan Dana Bantuan Operasional Sekolah dan Penyelenggaraan (BOSP) Tahap 1 Tahun 2025.</p><p>Publikasi ini diharapkan dapat menjadi bentuk pertanggungjawaban kepada wali santri, masyarakat, dan seluruh pemangku kepentingan pendidikan.</p>',
                'category' => 'SMAIT Nurul Islam',
                'author' => 'Redaksi Nuris',
                'author_initials' => 'RN',
                'read_time' => 3,
                'is_published' => true,
                'published_at' => Carbon::parse('2025-07-23'),
            ],
            [
                'title' => 'Pengumuman Kelulusan SMAIT Nurul Islam Tengaran Tahun Pelajaran 2024/2025',
                'slug' => 'pengumuman-kelulusan-smait-2024-2025',
                'excerpt' => 'Pengumuman resmi kelulusan yang ditujukan kepada orang tua/wali peserta didik kelas XII SMAIT Nurul Islam Tengaran.',
                'content' => '<p>Assalamu\'alaikum warahmatullahi wabarakatuh.</p><p>Dengan memanjatkan puji syukur ke hadirat Allah SWT, disampaikan pengumuman resmi kelulusan peserta didik kelas XII SMA Islam Terpadu Nurul Islam Tengaran Tahun Pelajaran 2024/2025 kepada seluruh orang tua/wali.</p><p>Kami mengucapkan selamat kepada seluruh peserta didik yang telah menyelesaikan pendidikannya. Semoga ilmu yang diperoleh menjadi bekal yang bermanfaat di jenjang berikutnya.</p>',
                'category' => 'SMAIT Nurul Islam',
                'author' => 'Redaksi Nuris',
                'author_initials' => 'RN',
                'read_time' => 2,
                'is_published' => true,
                'published_at' => Carbon::parse('2025-05-05'),
            ],
            [
                'title' => 'Keterbukaan Informasi Pengelolaan Dana BOSP SMAIT Nurul Islam Tengaran Tahun 2022 dan 2023',
                'slug' => 'keterbukaan-informasi-bosp-smait-2022-2023',
                'excerpt' => 'Di tengah dinamika pengelolaan dana pendidikan, transparansi menjadi kunci utama dalam menciptakan kepercayaan dan akuntabilitas.',
                'content' => '<p>Di tengah dinamika pengelolaan dana pendidikan, transparansi menjadi kunci utama dalam menciptakan kepercayaan dan akuntabilitas.</p><p>SMA Islam Terpadu Nurul Islam Tengaran menyampaikan laporan pengelolaan Dana Bantuan Operasional Sekolah dan Penyelenggaraan (BOSP) untuk Tahun 2022 dan 2023 sebagai bentuk keterbukaan informasi kepada publik.</p>',
                'category' => 'SMAIT Nurul Islam',
                'author' => 'Redaksi Nuris',
                'author_initials' => 'RN',
                'read_time' => 3,
                'is_published' => true,
                'published_at' => Carbon::parse('2025-05-02'),
            ],
            [
                'title' => 'Waktu-Waktu Penting di Bulan Ramadhan',
                'slug' => 'waktu-waktu-penting-di-bulan-ramadhan',
                'excerpt' => 'Panduan bagi orang tua untuk mengenalkan anak pada waktu-waktu penting selama bulan Ramadhan.',
                'content' => '<p>Bulan Ramadhan adalah momen istimewa untuk menanamkan kecintaan anak terhadap ibadah. Orang tua memiliki peran penting dalam mengenalkan waktu-waktu penting selama bulan suci ini.</p><p>Mulai dari waktu sahur, imsak, berbuka, hingga shalat tarawih, setiap momen dapat menjadi sarana pendidikan yang bermakna bagi buah hati agar tumbuh mencintai ibadah sejak dini.</p>',
                'category' => 'Yayasan',
                'author' => 'Redaksi Nuris',
                'author_initials' => 'RN',
                'read_time' => 3,
                'is_published' => true,
                'published_at' => Carbon::parse('2023-03-27'),
            ],
            [
                'title' => 'Telah Dibuka! Pendaftaran Penerimaan Santri Baru (PSB) Ponpes Nurul Islam Tengaran Tahun Ajaran 2023/2024',
                'slug' => 'psb-ponpes-nurul-islam-2023-2024',
                'excerpt' => 'Pendaftaran Penerimaan Santri Baru Ponpes Nurul Islam Tengaran untuk program SMPIT, SMAIT, dan MA telah dibuka.',
                'content' => '<p>Pondok Pesantren Nurul Islam Tengaran dengan bangga mengumumkan pembukaan Pendaftaran Penerimaan Santri Baru (PSB) untuk Tahun Ajaran 2023/2024.</p><p>Pendaftaran terbuka untuk program SMPIT, SMAIT, dan Madrasah Aliyah (MA) yang berbasis pesantren. Calon santri akan mendapatkan pendidikan terpadu antara ilmu agama dan ilmu umum dalam lingkungan yang islami.</p><p>Informasi lebih lanjut mengenai syarat dan alur pendaftaran dapat diperoleh melalui panitia PSB Nurul Islam Tengaran.</p>',
                'category' => 'Ponpes Nurul Islam',
                'author' => 'Redaksi Nuris',
                'author_initials' => 'RN',
                'read_time' => 3,
                'is_published' => true,
                'published_at' => Carbon::parse('2022-11-02'),
            ],
            [
                'title' => 'Ketahui Niat Puasa Syawal beserta Tata Cara dan 4 Keutamaannya',
                'slug' => 'niat-puasa-syawal-tata-cara-dan-keutamaan',
                'excerpt' => 'Puasa Syawal menjadi salah satu sunnah yang dianjurkan Nabi Muhammad SAW, dilaksanakan selama enam hari.',
                'content' => '<p>Puasa Syawal menjadi salah satu sunnah yang dianjurkan oleh Nabi Muhammad SAW, dilaksanakan selama enam hari di bulan Syawal setelah Idul Fitri.</p><p>Keutamaan puasa Syawal sangat besar. Rasulullah SAW bersabda bahwa siapa yang berpuasa Ramadhan kemudian mengikutinya dengan enam hari di bulan Syawal, maka pahalanya seperti berpuasa sepanjang tahun.</p><p>Puasa ini dapat dilaksanakan secara berturut-turut maupun terpisah, selama masih dalam bulan Syawal.</p>',
                'category' => 'Yayasan',
                'author' => 'Redaksi Nuris',
                'author_initials' => 'RN',
                'read_time' => 4,
                'is_published' => true,
                'published_at' => Carbon::parse('2022-05-10'),
            ],
        ];

        foreach ($posts as $data) {
            Post::create($data);
        }
    }
}
