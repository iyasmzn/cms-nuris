<?php

namespace Database\Seeders;

use App\Models\AlumniUniversity;
use Illuminate\Database\Seeder;

class AlumniUniversitySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Universitas Indonesia',            'url' => 'https://www.ui.ac.id',       'sort_order' => 1],
            ['name' => 'Universitas Gadjah Mada',          'url' => 'https://ugm.ac.id',          'sort_order' => 2],
            ['name' => 'Institut Teknologi Bandung',       'url' => 'https://www.itb.ac.id',      'sort_order' => 3],
            ['name' => 'UIN Sunan Kalijaga',               'url' => 'https://uin-suka.ac.id',     'sort_order' => 4],
            ['name' => 'Universitas Airlangga',            'url' => 'https://unair.ac.id',        'sort_order' => 5],
            ['name' => 'Universitas Diponegoro',           'url' => 'https://www.undip.ac.id',    'sort_order' => 6],
            ['name' => 'Universitas Al-Azhar Kairo',       'url' => 'https://www.azhar.eg',       'sort_order' => 7],
            ['name' => 'Universitas Brawijaya',            'url' => 'https://ub.ac.id',           'sort_order' => 8],
        ];

        foreach ($defaults as $data) {
            AlumniUniversity::firstOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
        }
    }
}
