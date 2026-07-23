<?php

namespace Database\Seeders;

use App\Models\Greeting;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class GreetingSeeder extends Seeder
{
    public function run(): void
    {
        if (Greeting::exists()) {
            return;
        }

        // Migrasi data lama dari pengaturan kepala sekolah (settings principal_*).
        $legacyName = Setting::get('principal_name');

        if ($legacyName) {
            Greeting::create([
                'name' => $legacyName,
                'position' => Setting::get('principal_title', 'Direktur'),
                'nip' => Setting::get('principal_nip'),
                'photo' => Setting::get('principal_photo'),
                'excerpt' => Setting::get('principal_excerpt'),
                'page_slug' => Setting::get('principal_page'),
                'is_published' => true,
                'sort_order' => 1,
            ]);

            return;
        }

        // Catatan: nama & teks sambutan pimpinan tidak tersedia di website resmi,
        // sehingga diisi netral. Silakan lengkapi dengan data pimpinan sebenarnya
        // melalui panel admin.
        $greetings = [
            [
                'name' => 'Pimpinan Nurul Islam Tengaran',
                'position' => 'Direktur',
                'nip' => null,
                'photo' => null,
                'excerpt' => "Assalamu'alaikum warahmatullahi wabarakatuh.\n\nSelamat datang di website resmi Nurul Islam Tengaran. Dengan penuh syukur kami menghadirkan media informasi ini sebagai sarana untuk lebih mendekatkan lembaga kami kepada masyarakat, wali santri, dan seluruh mitra pendidikan.\n\nSejalan dengan motto kami, \"Mendidik Generasi Beradab, Disiplin, Kreatif, dan Cerdas\", kami berkomitmen menyelenggarakan pendidikan Islam yang berkualitas dari jenjang KB/TKIT hingga SMAIT dan Madrasah Aliyah. Semoga kehadiran kami senantiasa membawa manfaat bagi umat.",
                'page_slug' => null,
                'is_published' => true,
                'sort_order' => 1,
            ],
        ];

        foreach ($greetings as $greeting) {
            Greeting::create($greeting);
        }
    }
}
