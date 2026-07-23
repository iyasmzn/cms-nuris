<?php

namespace Database\Seeders;

use App\Models\Stat;
use Illuminate\Database\Seeder;

class StatSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['icon' => '🕌', 'label' => 'Berdiri Sejak',      'value' => '1973',  'sub' => 'Yayasan Sabilul Khoirot',  'sort_order' => 1],
            ['icon' => '🏫', 'label' => 'Unit Pendidikan',    'value' => '6',     'sub' => 'TKIT hingga Ponpes',       'sort_order' => 2],
            ['icon' => '🎯', 'label' => 'Ekstrakurikuler',    'value' => '13+',   'sub' => 'Pengembangan bakat',       'sort_order' => 3],
            ['icon' => '📿', 'label' => 'Program Unggulan',   'value' => '8+',    'sub' => 'Berbasis pesantren',       'sort_order' => 4],
        ];

        foreach ($defaults as $data) {
            Stat::firstOrCreate(['label' => $data['label']], $data);
        }
    }
}
