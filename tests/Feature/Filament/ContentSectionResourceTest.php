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
