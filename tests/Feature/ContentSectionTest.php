<?php

namespace Tests\Feature;

use App\Models\ContentSection;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_a_published_section_with_its_cta_on_the_home_page(): void
    {
        ContentSection::factory()->create([
            'eyebrow' => 'Tentang Kami',
            'title' => 'Lingkungan Belajar yang Nyaman',
            'description' => '<p>Kurikulum nasional dipadukan dengan diniyah.</p>',
            'cta_label' => 'Kenali Kami',
            'cta_url' => '/profil',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tentang Kami')
            ->assertSee('Lingkungan Belajar yang Nyaman')
            ->assertSee('Kurikulum nasional dipadukan dengan diniyah.', false)
            ->assertSee('Kenali Kami')
            ->assertSee('href="/profil"', false);
    }

    public function test_it_hides_an_unpublished_section(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Tersembunyi',
            'is_published' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Seksi Tersembunyi');
    }

    public function test_it_omits_the_cta_button_when_the_link_is_empty(): void
    {
        ContentSection::factory()->withoutCta()->create([
            'title' => 'Seksi Tanpa Tombol',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Tanpa Tombol')
            ->assertDontSee('Selengkapnya');
    }

    public function test_the_image_position_controls_the_column_order(): void
    {
        $left = ContentSection::factory()->create([
            'title' => 'Gambar Kiri',
            'image' => 'content-sections/kiri.jpg',
            'image_position' => 'left',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('lg:order-1', false)
            ->assertSee('data-aos="fade-right"', false);

        $left->update(['image_position' => 'right']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-aos="fade-left"', false);
    }

    public function test_a_section_without_an_image_still_renders_its_text(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Tanpa Gambar',
            'image' => null,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Tanpa Gambar');
    }

    public function test_the_section_order_setting_controls_where_it_renders(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Paling Atas',
            'is_published' => true,
        ]);

        $section = ContentSection::first();

        Setting::set('section_order', json_encode([
            ['key' => $section->order_key, 'visible' => true],
            ['key' => 'section_faq', 'visible' => true],
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Paling Atas');
    }

    public function test_it_still_renders_when_missing_from_a_saved_section_order(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Baru Dibuat',
            'is_published' => true,
        ]);

        Setting::set('section_order', json_encode([
            ['key' => 'section_faq', 'visible' => true],
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Baru Dibuat');
    }

    public function test_a_stale_order_entry_for_a_deleted_section_is_ignored(): void
    {
        Setting::set('section_order', json_encode([
            ['key' => ContentSection::ORDER_KEY_PREFIX.'999', 'visible' => true],
            ['key' => 'section_faq', 'visible' => true],
        ]));

        $this->get(route('home'))->assertOk();
    }

    public function test_the_anchor_id_falls_back_to_the_record_id(): void
    {
        $section = ContentSection::factory()->create([
            'anchor' => null,
            'is_published' => true,
        ]);

        $this->assertSame('seksi-'.$section->id, $section->anchor_id);

        $section->update(['anchor' => 'Fasilitas Kami']);

        $this->assertSame('fasilitas-kami', $section->fresh()->anchor_id);
    }

    public function test_published_scope_returns_only_published_in_sort_order(): void
    {
        $second = ContentSection::factory()->create(['is_published' => true, 'sort_order' => 2]);
        $first = ContentSection::factory()->create(['is_published' => true, 'sort_order' => 1]);
        ContentSection::factory()->create(['is_published' => false, 'sort_order' => 0]);

        $published = ContentSection::published()->get();

        $this->assertCount(2, $published);
        $this->assertSame([$first->id, $second->id], $published->pluck('id')->all());
    }

    public function test_order_key_round_trips(): void
    {
        $section = ContentSection::factory()->create();

        $this->assertSame($section->id, ContentSection::idFromOrderKey($section->order_key));
        $this->assertNull(ContentSection::idFromOrderKey('section_faq'));
        $this->assertNull(ContentSection::idFromOrderKey(ContentSection::ORDER_KEY_PREFIX.'abc'));
    }
}
