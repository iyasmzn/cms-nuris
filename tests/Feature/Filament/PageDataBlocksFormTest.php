<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\StaticPages\Pages\CreateStaticPage;
use App\Filament\Resources\StaticPages\Pages\EditStaticPage;
use App\Models\Program;
use App\Models\StaticPage;
use App\Support\LandingHero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Form Halaman & Program: blok data (seksi halaman depan) beserta
 * pengaturannya, dan kepala halaman "Hero Landing".
 */
class PageDataBlocksFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_type_picker_groups_the_data_blocks(): void
    {
        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(CreateStaticPage::class)
            ->assertSee('Isian Manual')
            ->assertSee('Data Otomatis — seperti seksi halaman depan')
            ->assertSee('Statistik — angka capaian dari menu Statistik');
    }

    public function test_data_block_settings_are_saved(): void
    {
        $page = StaticPage::factory()->create(['blocks' => []]);

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->fillForm([
                'blocks' => [
                    [
                        'type' => 'data_programs',
                        'eyebrow' => 'Pilihan Program',
                        'heading' => 'Program Unggulan Kami',
                        'intro' => 'Program yang paling diminati.',
                        'items_limit' => 3,
                        'data_columns' => 2,
                        'show_more_link' => false,
                        'background' => 'alt',
                        'padding' => 'lg',
                    ],
                    [
                        'type' => 'data_contact',
                        'heading' => 'Hubungi Kami',
                        'contact_map' => false,
                        'contact_buttons' => true,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        [$programs, $contact] = $page->refresh()->blocks;

        $this->assertSame('data_programs', $programs['type']);
        $this->assertSame('Program Unggulan Kami', $programs['heading']);
        $this->assertSame('Program yang paling diminati.', $programs['intro']);
        $this->assertSame(3, (int) $programs['items_limit']);
        $this->assertSame(2, (int) $programs['data_columns']);
        $this->assertFalse((bool) $programs['show_more_link']);
        $this->assertSame('alt', $programs['background']);

        $this->assertSame('data_contact', $contact['type']);
        $this->assertFalse((bool) $contact['contact_map']);
        $this->assertTrue((bool) $contact['contact_buttons']);
    }

    public function test_a_category_or_album_no_longer_in_use_does_not_block_saving(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'data_faq', 'heading' => 'FAQ Lama', 'data_category' => 'Kategori Lama'],
                ['type' => 'data_gallery', 'heading' => 'Galeri Lama', 'data_album' => 999],
            ],
        ]);

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->call('save')
            ->assertHasNoFormErrors();

        [$faq, $gallery] = $page->refresh()->blocks;

        $this->assertSame('Kategori Lama', $faq['data_category']);
        $this->assertSame(999, (int) $gallery['data_album']);
    }

    public function test_landing_hero_is_saved_with_the_page(): void
    {
        $page = StaticPage::factory()->create(['hero' => null]);

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->fillForm([
                'hero' => [
                    'layout' => LandingHero::LAYOUT_LANDING,
                    'media_type' => 'image',
                    'badge' => 'Selamat Datang',
                    'title' => 'Membangun Generasi Qurani',
                    'highlight' => 'Sejak 1980',
                    'subtitle' => 'Pesantren dengan program tahfidz unggulan.',
                    'primary_label' => 'Daftar Sekarang',
                    'primary_url' => '/ppdb',
                    'align' => 'center',
                    'size' => 'full',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();
        $landingHero = $page->landing_hero;

        $this->assertNotNull($landingHero);
        $this->assertSame('Membangun Generasi Qurani', $landingHero->title);
        $this->assertSame('Sejak 1980', $landingHero->highlight);
        $this->assertSame('center', $landingHero->align);
        $this->assertSame('full', $landingHero->size);
        $this->assertSame([['label' => 'Daftar Sekarang', 'url' => '/ppdb', 'primary' => true, 'external' => false]], $landingHero->buttons);
    }

    public function test_landing_hero_button_link_must_be_a_safe_url(): void
    {
        $page = StaticPage::factory()->create(['hero' => null]);

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->fillForm([
                'hero' => [
                    'layout' => LandingHero::LAYOUT_LANDING,
                    'media_type' => 'image',
                    'primary_label' => 'Klik',
                    'primary_url' => 'javascript:alert(1)',
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['hero.primary_url' => 'regex']);

        $this->assertNull($page->refresh()->landing_hero);
    }

    public function test_program_form_saves_a_data_block_and_landing_hero(): void
    {
        // Kategori seed-an pabrik tidak selalu ada di daftar pilihan form.
        $program = Program::factory()->create(['blocks' => [], 'hero' => null, 'category' => null]);

        Livewire::actingAs($this->panelUser('Program'))
            ->test(EditProgram::class, ['record' => $program->id])
            ->fillForm([
                'blocks' => [
                    ['type' => 'data_testimonials', 'heading' => 'Kata Alumni', 'items_limit' => 4, 'data_columns' => 4],
                ],
                'hero' => [
                    'layout' => LandingHero::LAYOUT_LANDING,
                    'media_type' => 'image',
                    'highlight' => '30 Juz dalam 3 Tahun',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $program->refresh();

        $this->assertSame('data_testimonials', $program->blocks[0]['type']);
        $this->assertSame(4, (int) $program->blocks[0]['items_limit']);
        $this->assertSame('30 Juz dalam 3 Tahun', $program->landing_hero?->highlight);
        // Judul hero yang dikosongkan memakai judul program
        $this->assertSame($program->title, $program->landing_hero?->title);
    }
}
