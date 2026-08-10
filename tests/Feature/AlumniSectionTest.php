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

    public function test_the_logo_row_uses_the_default_marquee_behaviour(): void
    {
        AlumniUniversity::factory()->create(['is_active' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('speed: 18,', false)
            ->assertSee('autoplay: true,', false)
            ->assertSee('--alumni-logo-card-width: 216px', false)
            ->assertSee('--alumni-logo-height: 72px', false)
            ->assertSee('--alumni-logo-gap: 20px', false)
            ->assertSee('@mouseenter="hold()"', false)
            ->assertDontSee('alumni-marquee is-color', false);
    }

    public function test_the_configured_speed_direction_and_sizes_are_rendered(): void
    {
        AlumniUniversity::factory()->create(['is_active' => true]);

        Setting::setMany([
            'alumni_marquee_speed' => 45,
            'alumni_marquee_direction' => 'right',
            'alumni_marquee_logo_height' => 96,
            'alumni_marquee_card_width' => 260,
            'alumni_marquee_gap' => 8,
        ]);

        $this->get(route('home'))
            ->assertOk()
            // Arah ke kanan dikirim sebagai kecepatan bertanda negatif
            ->assertSee('speed: -45,', false)
            ->assertSee('--alumni-logo-card-width: 260px', false)
            ->assertSee('--alumni-logo-height: 96px', false)
            ->assertSee('--alumni-logo-gap: 8px', false);
    }

    public function test_pause_on_hover_can_be_turned_off(): void
    {
        AlumniUniversity::factory()->create(['is_active' => true]);

        Setting::set('alumni_marquee_pause_on_hover', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('@mouseenter="hold()"', false);
    }

    public function test_autoplay_can_be_turned_off_entirely(): void
    {
        AlumniUniversity::factory()->create(['is_active' => true]);

        Setting::set('alumni_marquee_autoplay', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('autoplay: false,', false)
            // Jeda saat kursor lewat tidak relevan bila barisnya memang diam
            ->assertDontSee('@mouseenter="hold()"', false);
    }

    public function test_out_of_range_and_unknown_marquee_values_fall_back_to_safe_ones(): void
    {
        AlumniUniversity::factory()->create(['is_active' => true]);

        Setting::setMany([
            'alumni_marquee_speed' => 9999,
            'alumni_marquee_direction' => 'diagonal',
            'alumni_marquee_logo_height' => 4,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('speed: 150,', false)
            ->assertSee('--alumni-logo-height: 32px', false);
    }

    public function test_logo_colour_and_campus_name_can_be_switched(): void
    {
        AlumniUniversity::factory()->create([
            'name' => 'Universitas Airlangga',
            'is_active' => true,
        ]);

        Setting::setMany([
            'alumni_marquee_grayscale' => false,
            'alumni_marquee_show_name' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('alumni-marquee is-color', false)
            ->assertDontSee('alumni-logo-name">', false)
            // Nama tetap dipakai sebagai judul & teks alternatif logo
            ->assertSee('Universitas Airlangga');
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
