<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->randomElement([
            'Riyadhus Shalihin', 'Bulughul Maram', 'Fiqih Islam Wa Adillatuhu',
            'Tafsir Ibnu Katsir', 'Shahih Bukhari', 'Shahih Muslim',
            'Al-Minhaj Syarh Shahih Muslim', 'Kitab At-Tauhid',
            'Aqidah Wasithiyyah', 'Al-Wajiz Fi Ushul Fiqih',
            'Fathul Bari', 'Zad Al-Maad', 'Al-Fiqh Al-Manhaji',
            'Matan Al-Ajrumiyyah', 'Alfiyah Ibnu Malik',
        ]).' '.Str::random(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(4),
            'author' => $this->faker->randomElement([
                'Imam An-Nawawi', 'Ibnu Hajar Al-Asqalani', 'Dr. Wahbah Az-Zuhaili',
                'Ibnu Katsir', 'Imam Al-Bukhari', 'Imam Muslim',
                'Ibnu Qayyim Al-Jauziyyah', 'Syaikh Al-Utsaimin',
            ]),
            'isbn' => $this->faker->isbn13(),
            'publisher' => $this->faker->randomElement([
                'Gema Insani', 'Pustaka Imam Syafi\'i', 'Darul Haq',
                'Penerbit Toobagus', 'Dar Ibn Hazm', 'Pustaka Arafah',
            ]),
            'published_year' => $this->faker->numberBetween(2010, 2024),
            'category' => $this->faker->randomElement([
                'Hadits', 'Tafsir', 'Fiqih', 'Nahwu & Sharaf', 'Aqidah', 'Akhlak',
            ]),
            'description' => $this->faker->paragraph(3),
            'cover_image' => null,
            'pages' => $this->faker->numberBetween(100, 800),
            'price' => $this->faker->randomElement([
                25000, 35000, 45000, 55000, 65000, 75000,
                85000, 95000, 110000, 125000, 150000,
            ]),
            'stock' => $this->faker->numberBetween(0, 50),
            'weight_gram' => $this->faker->numberBetween(150, 900),
            'is_available' => true,
            'sort_order' => 0,
        ];
    }
}
