<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Seed the education units (jenjang) that each run their own PPDB.
     */
    public function run(): void
    {
        $institutions = [
            [
                'slug' => 'tkit',
                'name' => 'KB & TKIT Nurul Islam',
                'short_name' => 'TKIT',
                'icon' => '🧸',
                'color' => 'danger',
                'description' => 'Kelompok Bermain dan Taman Kanak-kanak Islam Terpadu.',
                'sort_order' => 1,
            ],
            [
                'slug' => 'sdit',
                'name' => 'SD Islam Terpadu Nurul Islam',
                'short_name' => 'SDIT',
                'icon' => '🎒',
                'color' => 'info',
                'description' => 'Jenjang pendidikan dasar Islam terpadu untuk kelas 1 hingga 6.',
                'sort_order' => 2,
            ],
            [
                'slug' => 'smpit',
                'name' => 'SMP Islam Terpadu Nurul Islam',
                'short_name' => 'SMPIT',
                'icon' => '📘',
                'color' => 'success',
                'description' => 'Jenjang pendidikan menengah pertama Islam terpadu untuk kelas 7 hingga 9.',
                'sort_order' => 3,
            ],
            [
                'slug' => 'smait',
                'name' => 'SMA Islam Terpadu Nurul Islam',
                'short_name' => 'SMAIT',
                'icon' => '🎓',
                'color' => 'warning',
                'description' => 'Jenjang pendidikan menengah atas Islam terpadu untuk kelas 10 hingga 12.',
                'sort_order' => 4,
            ],
            [
                'slug' => 'ma',
                'name' => 'Madrasah Aliyah Nurul Islam',
                'short_name' => 'MA',
                'icon' => '📗',
                'color' => 'primary',
                'description' => 'Madrasah Aliyah berbasis pesantren untuk kelas 10 hingga 12.',
                'sort_order' => 5,
            ],
        ];

        foreach ($institutions as $institution) {
            Institution::updateOrCreate(
                ['slug' => $institution['slug']],
                array_merge($institution, ['is_active' => true]),
            );
        }
    }
}
