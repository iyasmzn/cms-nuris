<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProgramSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $programs = [
        [
            'title' => 'Bina Pribadi Islam',
            'excerpt' => 'Program pembinaan karakter untuk membentuk kepribadian Islami yang kokoh pada setiap santri.',
            'content' => '<p>Bina Pribadi Islam merupakan program unggulan pembinaan karakter yang bertujuan membentuk kepribadian Islami yang utuh pada diri santri. Melalui pendampingan rutin, santri dibimbing untuk memiliki akhlak mulia, kedisiplinan, serta ketangguhan mental dan spiritual.</p><p>Program ini menjadi ruh dari seluruh proses pendidikan di Nurul Islam Tengaran, sejalan dengan motto "Mendidik Generasi Beradab, Disiplin, Kreatif, dan Cerdas".</p>',
            'icon' => '🕌',
            'category' => 'Unggulan',
            'is_published' => true,
            'sort_order' => 1,
        ],
        [
            'title' => 'Tahfidz',
            'excerpt' => 'Program menghafal Al-Qur\'an untuk semua jenjang, dari SMP hingga SMA/MA.',
            'content' => '<p>Program unggulan Tahfidz diselenggarakan untuk semua jenjang, dari SMP hingga SMA/MA. Santri dibimbing untuk menghafal Al-Qur\'an secara bertahap dengan target hafalan yang terukur di setiap jenjangnya.</p><p>Pembinaan tahfidz didukung oleh para asatidz yang berpengalaman serta metode muraja\'ah yang konsisten agar hafalan santri terjaga dengan baik.</p>',
            'icon' => '📖',
            'category' => 'Al-Qur\'an',
            'is_published' => true,
            'sort_order' => 2,
        ],
        [
            'title' => 'Tahsin',
            'excerpt' => 'Melafalkan setiap huruf sesuai makhraj dengan haq dan mustahiqnya.',
            'content' => '<p>Tahsin adalah kegiatan melafalkan setiap huruf dari tempat keluarnya (makhraj) masing-masing sesuai dengan haq dan mustahiqnya. Program ini menjadi fondasi penting sebelum santri melangkah ke tahap menghafal Al-Qur\'an.</p><p>Dengan tahsin yang baik, bacaan santri menjadi lebih fasih, tartil, dan sesuai kaidah tajwid.</p>',
            'icon' => '🔤',
            'category' => 'Al-Qur\'an',
            'is_published' => true,
            'sort_order' => 3,
        ],
        [
            'title' => 'Tasmi\'',
            'excerpt' => 'Memperdengarkan hafalan santri kelas tahfizh kepada para mustami\'in.',
            'content' => '<p>Tasmi\' merupakan kegiatan memperdengarkan hafalan santri kelas tahfizh kepada para mustami\'in, asatidz, murobbi, orang tua, dan teman. Kegiatan ini menjadi sarana evaluasi sekaligus penguat hafalan santri.</p><p>Melalui tasmi\', santri belajar percaya diri dan bertanggung jawab atas hafalan yang telah dicapainya.</p>',
            'icon' => '🎧',
            'category' => 'Al-Qur\'an',
            'is_published' => true,
            'sort_order' => 4,
        ],
        [
            'title' => 'Mukhayyam Al-Qur\'an',
            'excerpt' => 'Kegiatan untuk lebih mendalami dan menambah hafalan Al-Qur\'an.',
            'content' => '<p>Mukhayyam Al-Qur\'an adalah kegiatan untuk lebih mendalami Al-Qur\'an, yaitu dengan menambah hafalan Al-Qur\'an dalam suasana yang khusus dan terfokus.</p><p>Kegiatan ini biasanya dikemas dalam bentuk karantina Al-Qur\'an sehingga santri dapat berkonsentrasi penuh menambah dan memperkuat hafalannya.</p>',
            'icon' => '⛺',
            'category' => 'Al-Qur\'an',
            'is_published' => true,
            'sort_order' => 5,
        ],
        [
            'title' => 'Mabit',
            'excerpt' => 'Sarana tarbiyah untuk membina ruhiyah dan membiasakan fisik beribadah.',
            'content' => '<p>Mabit (Malam Bina Iman dan Taqwa) merupakan salah satu sarana tarbiyah untuk membina ruhiyah, melembutkan hati, dan membiasakan fisik untuk beribadah.</p><p>Melalui rangkaian ibadah malam, tausiyah, dan muhasabah, santri dilatih untuk mendekatkan diri kepada Allah dan menumbuhkan kepekaan spiritual.</p>',
            'icon' => '🌙',
            'category' => 'Pembinaan',
            'is_published' => true,
            'sort_order' => 6,
        ],
        [
            'title' => 'Kajian Kitab',
            'excerpt' => 'Kegiatan rutin mengkaji kitab kuning yang dilakukan para santri.',
            'content' => '<p>Kajian kitab kuning merupakan salah satu kegiatan rutin yang dilakukan oleh para santri pada umumnya. Melalui kajian ini, santri mempelajari khazanah keilmuan Islam klasik dari para ulama.</p><p>Kegiatan ini membekali santri dengan pemahaman agama yang mendalam dan berlandaskan sumber-sumber yang otoritatif.</p>',
            'icon' => '📜',
            'category' => 'Pembinaan',
            'is_published' => true,
            'sort_order' => 7,
        ],
        [
            'title' => 'Tutorial Bahasa Asing',
            'excerpt' => 'Pembinaan keterampilan berbahasa asing, khususnya Arab dan Inggris.',
            'content' => '<p>Tutorial Bahasa Asing merupakan program pembinaan keterampilan berbahasa asing bagi santri, khususnya bahasa Arab dan bahasa Inggris.</p><p>Program ini bertujuan membekali santri agar mampu berkomunikasi secara aktif dalam dua bahasa asing sebagai bekal menghadapi tantangan global.</p>',
            'icon' => '🗣️',
            'category' => 'Bahasa',
            'is_published' => true,
            'sort_order' => 8,
        ],
        [
            'title' => 'Jumpa Native Speaker',
            'excerpt' => 'Kegiatan rutin di Ponpes Nurul Islam yang melibatkan penutur asli.',
            'content' => '<p>Salah satu rangkaian dari program rutin di Ponpes Nurul Islam adalah kegiatan yang melibatkan native speaker (penutur asli). Santri berkesempatan berinteraksi langsung untuk mengasah kemampuan berbahasa asing.</p><p>Kegiatan ini menumbuhkan rasa percaya diri santri dalam berkomunikasi dengan penutur asli.</p>',
            'icon' => '🌐',
            'category' => 'Bahasa',
            'is_published' => true,
            'sort_order' => 9,
        ],
        [
            'title' => 'Jumpa Tokoh',
            'excerpt' => 'Pertemuan antara santri dengan tokoh atau figur penting.',
            'content' => '<p>Jumpa Tokoh adalah kegiatan di pondok pesantren yang melibatkan pertemuan antara para santri dengan tokoh atau figur penting.</p><p>Melalui kegiatan ini, santri memperoleh inspirasi, motivasi, dan wawasan langsung dari para tokoh di berbagai bidang.</p>',
            'icon' => '🎤',
            'category' => 'Pembinaan',
            'is_published' => true,
            'sort_order' => 10,
        ],
        [
            'title' => 'OSIS',
            'excerpt' => 'Organisasi Siswa Intra Sekolah sebagai wadah kepemimpinan santri.',
            'content' => '<p>OSIS merupakan wadah bagi santri untuk berorganisasi, melatih kepemimpinan, dan mengembangkan kemampuan bekerja sama dalam berbagai kegiatan sekolah.</p>',
            'icon' => '🏛️',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 11,
        ],
        [
            'title' => 'Pramuka SIT',
            'excerpt' => 'Kepramukaan berbasis Sekolah Islam Terpadu.',
            'content' => '<p>Pramuka SIT adalah kegiatan kepramukaan yang dikembangkan dalam bingkai Sekolah Islam Terpadu untuk menumbuhkan kemandirian, kedisiplinan, dan cinta tanah air.</p>',
            'icon' => '⛺',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 12,
        ],
        [
            'title' => 'PMR',
            'excerpt' => 'Palang Merah Remaja untuk membina kepedulian dan keterampilan kesehatan.',
            'content' => '<p>PMR (Palang Merah Remaja) membina santri dalam bidang kesehatan, pertolongan pertama, dan kepedulian sosial terhadap sesama.</p>',
            'icon' => '🚑',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 13,
        ],
        [
            'title' => 'Science Club',
            'excerpt' => 'Kelompok eksplorasi sains bagi santri yang gemar meneliti.',
            'content' => '<p>Science Club menjadi wadah bagi santri untuk mengeksplorasi ilmu pengetahuan melalui eksperimen, penelitian, dan berbagai kegiatan ilmiah.</p>',
            'icon' => '🔬',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 14,
        ],
        [
            'title' => 'English Club',
            'excerpt' => 'Pengembangan kemampuan berbahasa Inggris secara aktif dan menyenangkan.',
            'content' => '<p>English Club merupakan kegiatan pengembangan kemampuan berbahasa Inggris santri melalui percakapan, permainan bahasa, dan berbagai aktivitas kreatif.</p>',
            'icon' => '🔤',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 15,
        ],
        [
            'title' => 'Sport Club',
            'excerpt' => 'Beragam pilihan olahraga: futsal, panahan, badminton, renang, voli, taekwondo.',
            'content' => '<p>Sport Club menyediakan beragam pilihan cabang olahraga bagi santri, di antaranya futsal, panahan (archery), badminton, renang, bola voli, dan taekwondo.</p><p>Kegiatan ini bertujuan menjaga kebugaran, menumbuhkan sportivitas, dan menyalurkan bakat santri di bidang olahraga.</p>',
            'icon' => '⚽',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 16,
        ],
        [
            'title' => 'Astronomi Islam',
            'excerpt' => 'Kajian ilmu falak dan astronomi dalam perspektif Islam.',
            'content' => '<p>Astronomi Islam merupakan kegiatan kajian ilmu falak dan astronomi dalam perspektif Islam, seperti penentuan arah kiblat, awal waktu shalat, dan penanggalan hijriah.</p>',
            'icon' => '🔭',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 17,
        ],
        [
            'title' => 'Rebana',
            'excerpt' => 'Seni musik perkusi islami yang membina kreativitas dan cinta seni.',
            'content' => '<p>Rebana adalah kegiatan seni musik perkusi tradisional islami yang membina kreativitas santri serta menumbuhkan kecintaan terhadap seni bernuansa religi.</p>',
            'icon' => '🥁',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 18,
        ],
        [
            'title' => 'Bela Diri (Thifan)',
            'excerpt' => 'Latihan bela diri Thifan untuk ketangguhan fisik dan mental.',
            'content' => '<p>Bela Diri Thifan merupakan kegiatan latihan bela diri yang melatih ketangguhan fisik, mental, serta kedisiplinan santri.</p>',
            'icon' => '🥋',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 19,
        ],
        [
            'title' => 'Peminatan Bahasa Arab & Umum',
            'excerpt' => 'Jalur peminatan bahasa Arab dan mata pelajaran umum sesuai minat santri.',
            'content' => '<p>Program peminatan menyediakan jalur bahasa Arab maupun mata pelajaran umum sesuai minat dan bakat santri, sebagai bekal melanjutkan ke jenjang berikutnya.</p>',
            'icon' => '📚',
            'category' => 'Ekstrakurikuler',
            'is_published' => true,
            'sort_order' => 20,
        ],
    ];

    public function run(): void
    {
        foreach ($this->programs as $data) {
            Program::firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                $data
            );
        }
    }
}
