<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_published_faqs_on_the_home_page(): void
    {
        Faq::factory()->create([
            'question' => 'Kapan pendaftaran dibuka?',
            'answer' => '<p>Setiap awal tahun ajaran baru.</p>',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kapan pendaftaran dibuka?')
            ->assertSee('Setiap awal tahun ajaran baru.', false)
            ->assertSee('Ada yang Ingin Ditanyakan?');
    }

    public function test_it_hides_unpublished_faqs(): void
    {
        Faq::factory()->create([
            'question' => 'Pertanyaan tersembunyi?',
            'is_published' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Pertanyaan tersembunyi?');
    }

    public function test_the_whole_section_disappears_when_there_is_no_faq(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Ada yang Ingin Ditanyakan?');
    }

    public function test_it_shows_custom_section_headings_when_set(): void
    {
        Faq::factory()->create(['is_published' => true]);

        Setting::setMany([
            'section_faq_eyebrow' => 'Tanya Jawab',
            'section_faq_title' => 'Pertanyaan Sering Diajukan',
            'section_faq_subtitle' => 'Deskripsi FAQ yang sudah diubah.',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tanya Jawab')
            ->assertSee('Pertanyaan Sering Diajukan')
            ->assertSee('Deskripsi FAQ yang sudah diubah.')
            ->assertDontSee('Ada yang Ingin Ditanyakan?');
    }

    public function test_the_section_can_be_hidden_from_landing_page_settings(): void
    {
        Faq::factory()->create(['question' => 'Kapan pendaftaran dibuka?', 'is_published' => true]);

        Setting::set('section_order', json_encode([
            ['key' => 'section_faq', 'visible' => false],
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Kapan pendaftaran dibuka?');
    }
}
