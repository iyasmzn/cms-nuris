<?php

namespace Tests\Feature;

use App\Models\AlumniStat;
use App\Models\AlumniUniversity;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_alumni_stats_on_the_home_page(): void
    {
        AlumniStat::factory()->create([
            'icon' => '🎓',
            'value' => '3.500+',
            'label' => 'Alumni Terdata',
            'sub' => 'Sejak angkatan pertama',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('3.500+')
            ->assertSee('Alumni Terdata')
            ->assertSee('Sejak angkatan pertama')
            ->assertSee('Ke Mana Alumni Kami Melangkah');
    }

    /**
     * Kartu dicetak sekali saja — penggandaan untuk gulir tak berujung baru
     * dikerjakan di browser, dan hanya bila barisnya melebihi lebar layar.
     */
    public function test_it_prints_each_active_university_logo_once(): void
    {
        AlumniUniversity::factory()->create([
            'name' => 'Universitas Gadjah Mada',
            'is_active' => true,
        ]);

        $response = $this->get(route('home'))->assertOk();

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'alumni-logo-name">Universitas Gadjah Mada'),
        );
        $response->assertSee('Kampus Tujuan Alumni');
    }

    public function test_it_hides_inactive_universities(): void
    {
        AlumniUniversity::factory()->create([
            'name' => 'Kampus Nonaktif',
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Kampus Nonaktif');
    }

    public function test_a_university_with_a_url_is_rendered_as_a_link(): void
    {
        AlumniUniversity::factory()->create([
            'name' => 'Universitas Indonesia',
            'url' => 'https://www.ui.ac.id',
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="https://www.ui.ac.id"', false);
    }

    public function test_the_whole_section_disappears_when_there_is_no_alumni_data(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Ke Mana Alumni Kami Melangkah')
            ->assertDontSee('Kampus Tujuan Alumni');
    }

    public function test_it_shows_custom_section_headings_when_set(): void
    {
        AlumniStat::factory()->create();
        AlumniUniversity::factory()->create(['is_active' => true]);

        Setting::setMany([
            'section_alumni_eyebrow' => 'Alumni Kami',
            'section_alumni_title' => 'Sebaran Alumni',
            'section_alumni_subtitle' => 'Deskripsi alumni yang sudah diubah.',
            'section_alumni_logos_title' => 'Kampus Pilihan Mereka',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Alumni Kami')
            ->assertSee('Sebaran Alumni')
            ->assertSee('Deskripsi alumni yang sudah diubah.')
            ->assertSee('Kampus Pilihan Mereka')
            ->assertDontSee('Ke Mana Alumni Kami Melangkah');
    }

    public function test_the_section_can_be_hidden_from_landing_page_settings(): void
    {
        AlumniStat::factory()->create(['label' => 'Alumni Terdata']);

        Setting::set('section_order', json_encode([
            ['key' => 'section_alumni', 'visible' => false],
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Alumni Terdata');
    }
}
