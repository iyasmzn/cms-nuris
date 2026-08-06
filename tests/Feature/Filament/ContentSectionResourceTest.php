<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ContentSections\Pages\CreateContentSection;
use App\Filament\Resources\ContentSections\Pages\EditContentSection;
use App\Filament\Resources\ContentSections\Pages\ListContentSections;
use App\Models\ContentSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentSectionResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->panelUser('ContentSection');
        $this->actingAs($this->user);
    }

    // ── List ──────────────────────────────────────────────────────

    public function test_list_page_can_render(): void
    {
        $sections = ContentSection::factory()->count(3)->create();

        Livewire::test(ListContentSections::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($sections);
    }

    public function test_list_page_can_search(): void
    {
        $visible = ContentSection::factory()->create(['title' => 'Fasilitas Lengkap']);
        $hidden = ContentSection::factory()->create(['title' => 'Prestasi Santri']);

        Livewire::test(ListContentSections::class)
            ->searchTable('Fasilitas')
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    // ── Create ────────────────────────────────────────────────────

    public function test_create_page_can_render(): void
    {
        Livewire::test(CreateContentSection::class)
            ->assertSuccessful();
    }

    public function test_can_create_content_section(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'eyebrow' => 'Tentang Kami',
                'title' => 'Lingkungan Belajar yang Nyaman',
                'description' => '<p>Kurikulum nasional dipadukan dengan diniyah.</p>',
                'image_position' => 'left',
                'background' => 'alt',
                'anchor' => 'tentang-kami',
                'cta_label' => 'Selengkapnya',
                'cta_url' => '/profil',
                'cta_new_tab' => true,
                'is_published' => true,
                'sort_order' => 3,
            ])
            ->call('create')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(ContentSection::class, [
            'title' => 'Lingkungan Belajar yang Nyaman',
            'image_position' => 'left',
            'background' => 'alt',
            'anchor' => 'tentang-kami',
            'cta_url' => '/profil',
            'cta_new_tab' => true,
            'sort_order' => 3,
        ]);
    }

    public function test_can_create_a_section_with_background_image_effects(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Seksi Berlatar Gambar',
                'description' => '<p>Deskripsi.</p>',
                'background' => 'image',
                'background_blur' => 8,
                'background_overlay' => 45,
                'background_parallax_mode' => 'scroll',
                'background_parallax_speed' => 50,
                'background_light_text' => true,
            ])
            ->call('create')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(ContentSection::class, [
            'title' => 'Seksi Berlatar Gambar',
            'background' => 'image',
            'background_blur' => 8,
            'background_overlay' => 45,
            'background_parallax_mode' => 'scroll',
            'background_parallax_speed' => 50,
            'background_light_text' => true,
        ]);
    }

    public function test_background_effect_fields_are_hidden_unless_the_background_is_an_image(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm(['background' => 'default'])
            ->assertFormFieldHidden('background_blur')
            ->assertFormFieldHidden('background_overlay')
            ->assertFormFieldHidden('background_parallax_mode')
            ->fillForm(['background' => 'image'])
            ->assertFormFieldVisible('background_blur')
            ->assertFormFieldVisible('background_overlay')
            ->assertFormFieldVisible('background_parallax_mode');
    }

    public function test_the_parallax_speed_only_appears_once_parallax_is_on(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm(['background' => 'image', 'background_parallax_mode' => 'none'])
            ->assertFormFieldHidden('background_parallax_speed')
            ->fillForm(['background_parallax_mode' => 'scroll'])
            ->assertFormFieldVisible('background_parallax_speed');
    }

    public function test_the_parallax_slider_rejects_a_value_outside_its_range(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Seksi Baru',
                'description' => '<p>Deskripsi.</p>',
                'background' => 'image',
                'background_parallax_mode' => 'scroll',
                'background_parallax_speed' => 150,
            ])
            ->call('create')
            ->assertHasFormErrors(['background_parallax_speed']);
    }

    // ── Kartu & carousel ──────────────────────────────────────────

    public function test_can_create_a_card_section_with_its_own_ctas(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Program Unggulan',
                'description' => '<p>Deskripsi.</p>',
                'layout' => 'cards',
                'items_columns' => 4,
                'items' => [
                    [
                        'title' => 'Tahfidz',
                        'description' => 'Menghafal Al-Qur\'an dengan pendampingan.',
                        'cta_label' => 'Selengkapnya',
                        'cta_url' => '/program/tahfidz',
                        'cta_new_tab' => false,
                    ],
                    [
                        'title' => 'Sains',
                        'cta_url' => 'https://sains.test',
                        'cta_new_tab' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $section = ContentSection::firstWhere('title', 'Program Unggulan');

        $this->assertSame('cards', $section->layout);
        $this->assertSame(4, $section->items_columns);
        $this->assertCount(2, $section->cards);
        $this->assertTrue($section->cards[0]->has_cta);
        $this->assertTrue($section->cards[1]->is_clickable);
        $this->assertTrue($section->cards[1]->cta_new_tab);
    }

    public function test_can_create_a_carousel_section_with_autoplay_settings(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Kartu Berjalan',
                'description' => '<p>Deskripsi.</p>',
                'layout' => 'carousel',
                'items' => [['title' => 'Kartu A'], ['title' => 'Kartu B']],
                'carousel_autoplay' => true,
                'carousel_autoplay_delay' => 8,
                'carousel_loop' => false,
                'carousel_arrows' => true,
                'carousel_dots' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(ContentSection::class, [
            'title' => 'Kartu Berjalan',
            'layout' => 'carousel',
            'carousel_autoplay' => true,
            'carousel_autoplay_delay' => 8,
            'carousel_loop' => false,
            'carousel_dots' => false,
        ]);
    }

    public function test_the_card_fields_only_appear_on_a_card_layout(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm(['layout' => 'media'])
            ->assertFormFieldVisible('image_position')
            ->assertFormFieldHidden('items')
            ->assertFormFieldHidden('items_columns')
            ->assertFormFieldHidden('carousel_autoplay')
            ->fillForm(['layout' => 'cards'])
            ->assertFormFieldHidden('image_position')
            ->assertFormFieldVisible('items')
            ->assertFormFieldVisible('items_columns')
            ->assertFormFieldHidden('carousel_autoplay')
            ->fillForm(['layout' => 'carousel'])
            ->assertFormFieldVisible('items')
            ->assertFormFieldVisible('carousel_autoplay')
            ->assertFormFieldVisible('carousel_loop');
    }

    public function test_the_autoplay_delay_only_appears_once_autoplay_is_on(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm(['layout' => 'carousel', 'carousel_autoplay' => false])
            ->assertFormFieldHidden('carousel_autoplay_delay')
            ->fillForm(['carousel_autoplay' => true])
            ->assertFormFieldVisible('carousel_autoplay_delay');
    }

    public function test_a_card_title_is_required_and_its_link_is_validated(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Seksi Kartu',
                'description' => '<p>Deskripsi.</p>',
                'layout' => 'cards',
                'items' => [
                    ['title' => null, 'cta_label' => 'Buka', 'cta_url' => 'javascript:alert(1)'],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseMissing(ContentSection::class, ['title' => 'Seksi Kartu']);
    }

    public function test_create_validates_required_fields(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => null,
                'description' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required'])
            ->assertHasNoFormErrors(['description'])
            ->assertNotNotified();
    }

    public function test_can_create_a_section_without_a_description(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Seksi Tanpa Deskripsi',
                'description' => null,
            ])
            ->call('create')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(ContentSection::class, [
            'title' => 'Seksi Tanpa Deskripsi',
            'description' => null,
        ]);
    }

    public function test_can_save_typography_settings_for_each_text_element(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Seksi Bergaya',
                'description' => '<p>Deskripsi.</p>',
                'layout' => 'cards',
                'typography' => [
                    'title' => ['align' => 'center', 'weight' => 800, 'size' => 'xl', 'color' => '#123456'],
                    'card_title' => ['align' => 'right', 'weight' => 600, 'size' => 'sm', 'color' => null],
                ],
            ])
            ->call('create')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $section = ContentSection::where('title', 'Seksi Bergaya')->sole();

        $this->assertSame('center', $section->typography['title']['align']);
        $this->assertSame(800, (int) $section->typography['title']['weight']);
        $this->assertSame('xl', $section->typography['title']['size']);
        $this->assertSame('#123456', $section->typography['title']['color']);
        $this->assertSame('right', $section->typography['card_title']['align']);
    }

    public function test_cta_label_and_url_require_each_other(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Seksi Baru',
                'description' => '<p>Deskripsi.</p>',
                'cta_label' => 'Selengkapnya',
                'cta_url' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['cta_url']);
    }

    public function test_cta_url_rejects_an_unsupported_scheme(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => 'Seksi Baru',
                'description' => '<p>Deskripsi.</p>',
                'cta_label' => 'Selengkapnya',
                'cta_url' => 'javascript:alert(1)',
            ])
            ->call('create')
            ->assertHasFormErrors(['cta_url']);
    }

    // ── Edit ──────────────────────────────────────────────────────

    public function test_edit_page_can_render(): void
    {
        $section = ContentSection::factory()->create();

        Livewire::test(EditContentSection::class, ['record' => $section->id])
            ->assertSuccessful()
            ->assertFormSet([
                'title' => $section->title,
                'image_position' => $section->image_position,
            ]);
    }

    public function test_can_edit_content_section(): void
    {
        $section = ContentSection::factory()->create(['image_position' => 'left']);

        Livewire::test(EditContentSection::class, ['record' => $section->id])
            ->fillForm([
                'title' => 'Judul Diperbarui',
                'image_position' => 'right',
                'is_published' => false,
            ])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(ContentSection::class, [
            'id' => $section->id,
            'title' => 'Judul Diperbarui',
            'image_position' => 'right',
            'is_published' => false,
        ]);
    }

    // ── Model / Factory ───────────────────────────────────────────

    public function test_factory_creates_valid_records(): void
    {
        $section = ContentSection::factory()->create();

        $this->assertDatabaseHas(ContentSection::class, ['id' => $section->id]);
        $this->assertNotEmpty($section->title);
        $this->assertNotEmpty($section->description);
    }
}
