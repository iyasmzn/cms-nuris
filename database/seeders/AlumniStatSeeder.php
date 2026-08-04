<?php

namespace Database\Seeders;

use App\Models\AlumniStat;
use Illuminate\Database\Seeder;

class AlumniStatSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['icon' => '🎓', 'label' => 'Alumni Terdata',     'value' => '3.500+', 'sub' => 'Sejak angkatan pertama',    'sort_order' => 1],
            ['icon' => '🏛️', 'label' => 'Perguruan Tinggi',   'value' => '80+',    'sub' => 'Negeri & swasta',           'sort_order' => 2],
            ['icon' => '🌏', 'label' => 'Kuliah Luar Negeri',  'value' => '25+',    'sub' => 'Mesir, Yaman, Malaysia',    'sort_order' => 3],
            ['icon' => '💼', 'label' => 'Terserap Kerja',      'value' => '92%',    'sub' => 'Dalam 1 tahun kelulusan',   'sort_order' => 4],
        ];

        foreach ($defaults as $data) {
            AlumniStat::firstOrCreate(['label' => $data['label']], $data);
        }
    }
}
