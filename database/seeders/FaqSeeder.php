<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        if (Faq::exists()) {
            return;
        }

        $faqs = [
            [
                'question' => 'Kapan pendaftaran peserta didik baru dibuka?',
                'answer' => '<p>Pendaftaran dibuka setiap tahun ajaran baru sesuai gelombang yang diumumkan di halaman SPMB. Jadwal dan kuota tiap gelombang dapat dilihat langsung pada portal pendaftaran.</p>',
                'category' => 'SPMB',
            ],
            [
                'question' => 'Berkas apa saja yang harus disiapkan saat mendaftar?',
                'answer' => '<p>Umumnya calon peserta didik perlu menyiapkan:</p><ul><li>Fotokopi akta kelahiran dan kartu keluarga</li><li>Rapor semester terakhir</li><li>Ijazah atau surat keterangan lulus</li><li>Pas foto terbaru</li></ul><p>Rincian lengkap tercantum pada formulir pendaftaran masing-masing jenjang.</p>',
                'category' => 'SPMB',
            ],
            [
                'question' => 'Apakah pendaftaran bisa dilakukan secara online?',
                'answer' => '<p>Bisa. Seluruh proses pendaftaran mulai dari pengisian formulir, unggah berkas, hingga pemantauan status dilakukan melalui portal SPMB di website ini.</p>',
                'category' => 'SPMB',
            ],
            [
                'question' => 'Berapa biaya pendaftaran dan biaya pendidikan?',
                'answer' => '<p>Besaran biaya berbeda untuk tiap jenjang dan jalur pendaftaran. Informasi resmi disampaikan pada halaman SPMB atau dapat ditanyakan langsung ke panitia melalui kontak yang tersedia.</p>',
                'category' => 'Biaya',
            ],
            [
                'question' => 'Bagaimana cara mengetahui hasil seleksi?',
                'answer' => '<p>Hasil seleksi diumumkan melalui halaman status pendaftaran menggunakan nomor pendaftaran yang Anda terima setelah formulir dikirim.</p>',
                'category' => 'SPMB',
            ],
            [
                'question' => 'Apakah tersedia asrama untuk santri?',
                'answer' => '<p>Ya, tersedia fasilitas asrama dengan pendampingan pengasuh. Ketersediaan kamar mengikuti kuota tiap tahun ajaran, sehingga disarankan mendaftar lebih awal.</p>',
                'category' => 'Fasilitas',
            ],
            [
                'question' => 'Kegiatan ekstrakurikuler apa saja yang tersedia?',
                'answer' => '<p>Terdapat beragam pilihan ekstrakurikuler, mulai dari tahfidz, kajian kitab, pramuka, olahraga, hingga seni dan keterampilan. Setiap peserta didik dianjurkan mengikuti minimal satu kegiatan.</p>',
                'category' => 'Akademik',
            ],
            [
                'question' => 'Bagaimana cara menghubungi pihak sekolah?',
                'answer' => '<p>Silakan gunakan informasi kontak pada bagian Kontak Kami di halaman ini, atau datang langsung ke kantor sekretariat pada jam kerja.</p>',
                'category' => null,
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::create([
                ...$faq,
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
