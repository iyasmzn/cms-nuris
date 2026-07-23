<?php

namespace Database\Seeders;

use App\Models\ContactItem;
use Illuminate\Database\Seeder;

class ContactItemSeeder extends Seeder
{
    public function run(): void
    {
        if (ContactItem::exists()) {
            return;
        }

        $items = [
            [
                'icon' => '📍',
                'label' => 'Alamat',
                'value' => 'Jl. Raya Salatiga – Solo KM 8, Butuh, RT 20 RW 11, Kecamatan Tengaran, Kabupaten Semarang',
                'link' => 'https://maps.google.com/?q=Nurul+Islam+Tengaran+Kabupaten+Semarang',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'icon' => '📞',
                'label' => 'Telepon',
                'value' => '(0298) 3429395',
                'link' => 'tel:+622983429395',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'icon' => '✉️',
                'label' => 'Email',
                'value' => 'ypisabkho@gmail.com',
                'link' => 'mailto:ypisabkho@gmail.com',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'icon' => '📷',
                'label' => 'Instagram',
                'value' => '@nurulislam_tengaran',
                'link' => 'https://instagram.com/nurulislam_tengaran',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'icon' => '🕐',
                'label' => 'Jam Operasional',
                'value' => 'Senin–Sabtu, 07.00–15.00 WIB',
                'link' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            ContactItem::create($item);
        }
    }
}
