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

    public function test_create_validates_required_fields(): void
    {
        Livewire::test(CreateContentSection::class)
            ->fillForm([
                'title' => null,
                'description' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title' => 'required',
                'description',
            ])
            ->assertNotNotified();
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
