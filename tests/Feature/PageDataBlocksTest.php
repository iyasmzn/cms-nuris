<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Album;
use App\Models\AlumniStat;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Greeting;
use App\Models\Institution;
use App\Models\Media;
use App\Models\Post;
use App\Models\Program;
use App\Models\RegistrationWave;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\StaticPage;
use App\Models\Testimonial;
use App\Support\LandingBlocks;
use App\Support\LandingHero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman & program bisa memasang seksi halaman depan sebagai blok data
 * (isinya dari menu sumbernya, judul & latarnya per blok) dan bisa memakai
 * kepala halaman "Hero Landing" seperti halaman depan.
 */
class PageDataBlocksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function pageWith(array $blocks, array $attributes = []): StaticPage
    {
        return StaticPage::factory()->create([...$attributes, 'blocks' => $blocks, 'show_sidebar' => false]);
    }

    public function test_stats_block_shows_the_stats_menu_under_its_own_heading(): void
    {
        Stat::factory()->create(['label' => 'Santri Aktif', 'value' => '850']);

        $page = $this->pageWith([
            ['type' => 'data_stats', 'eyebrow' => 'Angka Kami', 'heading' => 'Capaian Sekolah', 'intro' => 'Data terbaru tahun ini.', 'background' => 'alt'],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('block-section block-section-full', false)
            ->assertSee('ldb ldb-stats', false)
            ->assertSeeInOrder(['Angka Kami', 'Capaian Sekolah', 'Data terbaru tahun ini.', 'Santri Aktif']);
    }

    public function test_data_blocks_without_data_are_left_out(): void
    {
        $page = $this->pageWith([
            ['type' => 'data_testimonials', 'heading' => 'Kata Alumni'],
            ['type' => 'data_faq', 'heading' => 'Pertanyaan Umum'],
            ['type' => 'data_spmb', 'heading' => 'Ayo Mendaftar'],
            ['type' => 'rich_text', 'heading' => 'Tetap Tampil', 'content' => '<p>Isi manual.</p>'],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertDontSee('Kata Alumni')
            ->assertDontSee('Pertanyaan Umum')
            ->assertDontSee('Ayo Mendaftar')
            ->assertSee('Tetap Tampil');
    }

    public function test_programs_block_can_be_narrowed_to_a_category_and_limited(): void
    {
        Program::factory()->count(4)->create(['category' => 'Tahfidz']);
        Program::factory()->create(['category' => 'Bahasa', 'title' => 'Program Bahasa Arab']);

        $page = $this->pageWith([
            ['type' => 'data_programs', 'heading' => 'Program Tahfidz', 'data_category' => 'Tahfidz', 'items_limit' => 3, 'data_columns' => 2],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('ldb-grid ldb-cols-2', false)
            ->assertSee('Lihat Semua Program')
            ->assertDontSee('Program Bahasa Arab');

        $programs = LandingBlocks::prepare($page->blocks)[0]['data'];

        $this->assertCount(3, $programs);
        $this->assertSame(['Tahfidz'], $programs->pluck('category')->unique()->values()->all());
    }

    public function test_the_see_all_link_can_be_switched_off(): void
    {
        Event::factory()->create(['title' => 'Wisuda Akbar', 'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3)]);

        $page = $this->pageWith([
            ['type' => 'data_events', 'heading' => 'Agenda', 'show_more_link' => false],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Wisuda Akbar')
            ->assertDontSee('Semua Kegiatan');
    }

    public function test_events_block_only_lists_upcoming_events(): void
    {
        Event::factory()->create(['title' => 'Kajian Pekan Depan', 'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(2)]);
        Event::factory()->create(['title' => 'Kajian Bulan Lalu', 'starts_at' => now()->subMonth(), 'ends_at' => now()->subMonth()->addHours(2)]);

        $page = $this->pageWith([['type' => 'data_events', 'heading' => 'Agenda Terdekat']]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Kajian Pekan Depan')
            ->assertDontSee('Kajian Bulan Lalu');
    }

    public function test_program_page_does_not_list_itself_in_a_programs_block(): void
    {
        $other = Program::factory()->create(['title' => 'Program Lain Yang Tampil']);
        $program = Program::factory()->create([
            'title' => 'Program Yang Dibuka',
            'show_sidebar' => false,
            'blocks' => [['type' => 'data_programs', 'heading' => 'Program Lainnya']],
        ]);

        $blocks = LandingBlocks::prepare($program->blocks, currentProgram: $program);

        $this->assertSame([$other->id], $blocks[0]['data']->modelKeys());

        $this->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('Program Lainnya')
            ->assertSee('Program Lain Yang Tampil');
    }

    public function test_posts_block_can_drop_the_featured_post(): void
    {
        Post::factory()->count(3)->create();

        $featured = $this->pageWith([['type' => 'data_posts', 'heading' => 'Berita']]);
        $plain = $this->pageWith([['type' => 'data_posts', 'heading' => 'Berita', 'posts_featured' => false]]);

        $this->get(route('page.show', $featured->slug))
            ->assertOk()
            ->assertSee('fi-card overflow-hidden mb-6', false);

        $this->get(route('page.show', $plain->slug))
            ->assertOk()
            ->assertDontSee('fi-card overflow-hidden mb-6', false)
            ->assertSee('Lihat Semua Artikel');
    }

    public function test_faq_block_can_be_narrowed_to_one_category(): void
    {
        Faq::factory()->published()->create(['question' => 'Berapa biaya pendaftaran?', 'category' => 'SPMB']);
        Faq::factory()->published()->create(['question' => 'Kapan jam besuk?', 'category' => 'Asrama']);

        $page = $this->pageWith([['type' => 'data_faq', 'heading' => 'FAQ Pendaftaran', 'data_category' => 'SPMB']]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Berapa biaya pendaftaran?')
            ->assertDontSee('Kapan jam besuk?')
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_gallery_block_uses_the_chosen_columns_and_album(): void
    {
        Media::factory()->inGallery()->inAlbum('Wisuda')->create(['name' => 'Foto Wisuda']);
        Media::factory()->inGallery()->inAlbum('Asrama')->create(['name' => 'Foto Asrama']);

        $page = $this->pageWith([[
            'type' => 'data_gallery',
            'heading' => 'Galeri Wisuda',
            'data_columns' => 4,
            'data_album' => Album::findByName('Wisuda')->id,
        ]]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('ldb-grid ldb-cols-4 ldb-grid-dense', false)
            ->assertSee('Foto Wisuda')
            ->assertDontSee('Foto Asrama');
    }

    public function test_contact_block_uses_theme_colours_and_can_hide_its_buttons(): void
    {
        Setting::setMany([
            'contact_phone' => '0812-3456-7890',
            'social_whatsapp' => '6281234567890',
        ]);

        $withButtons = $this->pageWith([['type' => 'data_contact', 'heading' => 'Hubungi Kami']]);
        $withoutButtons = $this->pageWith([['type' => 'data_contact', 'heading' => 'Hubungi Kami', 'contact_buttons' => false]]);

        $this->get(route('page.show', $withButtons->slug))
            ->assertOk()
            ->assertSee('0812-3456-7890')
            ->assertSee('fi-card fi-card-hover group relative overflow-hidden p-7', false)
            ->assertDontSee('bg-white/5 backdrop-blur-sm p-7', false)
            ->assertSee('Chat WhatsApp');

        $this->get(route('page.show', $withoutButtons->slug))
            ->assertOk()
            ->assertSee('0812-3456-7890')
            ->assertDontSee('Chat WhatsApp');
    }

    public function test_spmb_card_block_appears_while_a_jenjang_takes_registrations(): void
    {
        $year = AcademicYear::factory()->active()->create();
        $institution = Institution::factory()->create(['name' => 'SMP Contoh']);
        RegistrationWave::factory()->open()->create([
            'academic_year_id' => $year->id,
            'institution_id' => $institution->id,
        ]);

        $page = $this->pageWith([['type' => 'data_spmb']]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('ldb ldb-spmb', false)
            ->assertSee('SMP Contoh')
            ->assertSee('Dibuka');
    }

    public function test_greetings_alumni_testimonials_quick_links_and_steps_render(): void
    {
        Greeting::factory()->published()->create(['name' => 'Ustadz Pengasuh']);
        AlumniStat::factory()->create(['label' => 'Alumni Kampus Negeri']);
        Testimonial::factory()->published()->create(['name' => 'Alumni Berkesan']);

        $page = $this->pageWith([
            ['type' => 'data_greetings', 'heading' => 'Sambutan'],
            ['type' => 'data_alumni', 'heading' => 'Jejak Alumni'],
            ['type' => 'data_testimonials', 'heading' => 'Kesan Alumni'],
            ['type' => 'data_quick_links', 'heading' => 'Tautan Cepat'],
            ['type' => 'data_spmb_steps', 'heading' => 'Cara Mendaftar'],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSeeInOrder(['Ustadz Pengasuh', 'Alumni Kampus Negeri', 'Alumni Berkesan', 'Unduhan', 'Isi Formulir Online']);
    }

    public function test_data_block_headings_join_the_sidebar_table_of_contents_only_when_shown(): void
    {
        Stat::factory()->create();

        $page = StaticPage::factory()->create([
            'show_sidebar' => true,
            'blocks' => [
                ['type' => 'data_stats', 'heading' => 'Capaian Kami'],
                ['type' => 'data_testimonials', 'heading' => 'Kesan Kosong'],
                ['type' => 'rich_text', 'heading' => 'Sejarah', 'content' => '<p>Berdiri sejak 1980.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Daftar Isi')
            ->assertSeeInOrder(['Capaian Kami', 'Sejarah'])
            ->assertDontSee('Kesan Kosong');
    }

    public function test_unknown_limit_and_column_values_fall_back_to_defaults(): void
    {
        $this->assertSame(6, LandingBlocks::limit(['type' => 'data_programs', 'items_limit' => 999]));
        $this->assertSame(3, LandingBlocks::limit(['type' => 'data_events']));
        $this->assertSame(9, LandingBlocks::limit(['type' => 'data_posts', 'items_limit' => '9']));
        $this->assertSame('ldb-grid ldb-cols-3', LandingBlocks::gridClass(['data_columns' => 7]));
        $this->assertSame('ldb-grid ldb-cols-4', LandingBlocks::gridClass(['data_columns' => '4']));
    }

    public function test_landing_hero_replaces_the_standard_page_head(): void
    {
        $page = StaticPage::factory()->create([
            'title' => 'Profil Pesantren',
            'hero' => [
                'layout' => LandingHero::LAYOUT_LANDING,
                'badge' => 'Selamat Datang di {site}',
                'title' => 'Membangun Generasi Qurani',
                'highlight' => 'Sejak 1980',
                'subtitle' => 'Pesantren dengan program tahfidz unggulan.',
                'primary_label' => 'Daftar Sekarang',
                'primary_url' => '/ppdb',
                'secondary_label' => 'Lihat Video',
                'secondary_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'align' => 'center',
                'size' => 'full',
            ],
        ]);

        Setting::setMany(['site_name' => 'Pesantren Nurul Ilmi']);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('landing-hero page-band -mt-17 landing-hero-full landing-hero-align-center', false)
            ->assertSee('Selamat Datang di Pesantren Nurul Ilmi')
            ->assertSeeInOrder(['Membangun Generasi Qurani', 'Sejak 1980', 'Pesantren dengan program tahfidz unggulan.', 'Daftar Sekarang', 'Lihat Video'])
            ->assertSee('href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"', false)
            ->assertSee('target="_blank"', false)
            ->assertDontSee('Halaman Informasi');
    }

    public function test_standard_page_head_stays_the_default(): void
    {
        $page = StaticPage::factory()->create(['hero' => null]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Halaman Informasi')
            ->assertDontSee('landing-hero page-band', false)
            // Garis aksen selebar layar dulu mencuat di bawah pita yang membulat
            ->assertDontSee('<div style="height:4px;background:linear-gradient(90deg,var(--primary)', false);
    }

    public function test_landing_hero_falls_back_to_the_record_title_and_safe_options(): void
    {
        $hero = LandingHero::fromArray([
            'layout' => LandingHero::LAYOUT_LANDING,
            'title' => '   ',
            'align' => 'diagonal',
            'size' => 'raksasa',
            'primary_label' => 'Tanpa Tautan',
        ], 'Judul Halaman');

        $this->assertSame('Judul Halaman', $hero->title);
        $this->assertSame('left', $hero->align);
        $this->assertSame('lg', $hero->size);
        $this->assertSame([], $hero->buttons);
        $this->assertFalse(LandingHero::isEnabled(['layout' => 'standard']));
        $this->assertFalse(LandingHero::isEnabled(null));
    }

    public function test_program_can_use_the_landing_hero(): void
    {
        $program = Program::factory()->create([
            'title' => 'Tahfidz Intensif',
            'hero' => ['layout' => LandingHero::LAYOUT_LANDING, 'highlight' => '30 Juz dalam 3 Tahun'],
        ]);

        $this->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('landing-hero page-band', false)
            ->assertSeeInOrder(['Tahfidz Intensif', '30 Juz dalam 3 Tahun'])
            // Gambar program jadi latar cadangan hero
            ->assertSee($program->thumbnail_url, false);
    }
}
