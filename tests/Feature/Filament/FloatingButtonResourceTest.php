<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\FloatingButtons\Pages\CreateFloatingButton;
use App\Filament\Resources\FloatingButtons\Pages\EditFloatingButton;
use App\Filament\Resources\FloatingButtons\Pages\ListFloatingButtons;
use App\Models\FloatingButton;
use App\Models\Institution;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FloatingButtonResourceTest extends TestCase
{
    use RefreshDatabase;

    private Institution $sd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->panelUser('FloatingButton'));
        $this->sd = Institution::factory()->create(['name' => 'SD', 'slug' => 'sd']);
    }

    public function test_list_page_shows_where_each_button_appears(): void
    {
        $everywhere = FloatingButton::factory()->create();
        $sdOnly = FloatingButton::factory()->onlyOn(["ppdb:{$this->sd->id}"])->create();
        $notOnPpdb = FloatingButton::factory()->exceptOn(['group:ppdb'])->create();

        Livewire::test(ListFloatingButtons::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$everywhere, $sdOnly, $notOnPpdb])
            ->assertTableColumnStateSet('display_mode', 'Semua halaman', $everywhere)
            ->assertTableColumnStateSet('display_mode', 'Hanya: PPDB SD', $sdOnly)
            ->assertTableColumnStateSet('display_mode', 'Kecuali: PPDB (semua jenjang)', $notOnPpdb);
    }

    public function test_create_page_can_render(): void
    {
        Livewire::test(CreateFloatingButton::class)
            ->assertSuccessful()
            ->assertFormSet(['display_mode' => FloatingButton::DISPLAY_ALL]);
    }

    public function test_can_create_a_button_for_one_jenjang_ppdb_page(): void
    {
        Livewire::test(CreateFloatingButton::class)
            ->fillForm([
                'label' => 'WA Panitia SD',
                'url' => 'https://wa.me/6281234567890',
                'display_mode' => FloatingButton::DISPLAY_ONLY,
                'display_targets' => ["ppdb:{$this->sd->id}"],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $button = FloatingButton::query()->where('label', 'WA Panitia SD')->firstOrFail();

        $this->assertSame(FloatingButton::DISPLAY_ONLY, $button->display_mode);
        $this->assertSame(["ppdb:{$this->sd->id}"], $button->display_targets);
    }

    public function test_targets_are_required_unless_showing_on_every_page(): void
    {
        Livewire::test(CreateFloatingButton::class)
            ->fillForm([
                'label' => 'WA Umum',
                'url' => 'https://wa.me/6281234567890',
                'display_mode' => FloatingButton::DISPLAY_EXCEPT,
                'display_targets' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['display_targets' => 'required']);

        $this->assertDatabaseMissing(FloatingButton::class, ['label' => 'WA Umum']);
    }

    public function test_unknown_targets_are_rejected(): void
    {
        Livewire::test(CreateFloatingButton::class)
            ->fillForm([
                'label' => 'WA Umum',
                'url' => 'https://wa.me/6281234567890',
                'display_mode' => FloatingButton::DISPLAY_ONLY,
                'display_targets' => ['ppdb:999999'],
            ])
            ->call('create')
            ->assertHasFormErrors(['display_targets.0']);

        $this->assertDatabaseMissing(FloatingButton::class, ['label' => 'WA Umum']);
    }

    public function test_switching_back_to_every_page_clears_targets(): void
    {
        $button = FloatingButton::factory()->onlyOn(["ppdb:{$this->sd->id}"])->create();

        Livewire::test(EditFloatingButton::class, ['record' => $button->id])
            ->fillForm(['display_mode' => FloatingButton::DISPLAY_ALL])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $button->refresh();

        $this->assertSame(FloatingButton::DISPLAY_ALL, $button->display_mode);
        $this->assertNull($button->display_targets);
    }

    public function test_edit_form_drops_targets_whose_page_was_deleted(): void
    {
        $smp = Institution::factory()->create(['name' => 'SMP', 'slug' => 'smp']);
        $button = FloatingButton::factory()->onlyOn(["ppdb:{$this->sd->id}", "ppdb:{$smp->id}"])->create();
        $smp->delete();

        Livewire::test(EditFloatingButton::class, ['record' => $button->id])
            ->assertFormSet(['display_targets' => ["ppdb:{$this->sd->id}"]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(["ppdb:{$this->sd->id}"], $button->fresh()->display_targets);
    }

    public function test_icon_can_be_picked_from_the_media_library(): void
    {
        $media = Media::factory()->create(['path' => 'media/whatsapp.png', 'mime_type' => 'image/png']);

        Livewire::test(CreateFloatingButton::class)
            ->fillForm([
                'label' => 'WA Panitia',
                'url' => 'https://wa.me/6281234567890',
                'icon_image_source' => 'library',
                'icon_image_library' => $media->id,
            ])
            ->assertFormFieldIsVisible('icon_image_library')
            ->assertFormFieldIsHidden('icon_image')
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas(FloatingButton::class, [
            'label' => 'WA Panitia',
            'icon_image' => 'media/whatsapp.png',
        ]);
        $this->assertSame(1, Media::query()->count());
    }

    public function test_library_source_requires_a_selected_media(): void
    {
        Livewire::test(CreateFloatingButton::class)
            ->fillForm([
                'label' => 'WA Panitia',
                'url' => 'https://wa.me/6281234567890',
                'icon_image_source' => 'library',
                'icon_image_library' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['icon_image_library' => 'required']);

        $this->assertDatabaseMissing(FloatingButton::class, ['label' => 'WA Panitia']);
    }

    public function test_uploaded_icon_is_kept_untouched_and_added_to_the_media_library(): void
    {
        Storage::fake('public');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';

        Livewire::test(CreateFloatingButton::class)
            ->fillForm([
                'label' => 'WA Panitia',
                'url' => 'https://wa.me/6281234567890',
                'icon_image' => UploadedFile::fake()->createWithContent('whatsapp.svg', $svg),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $path = FloatingButton::query()->where('label', 'WA Panitia')->value('icon_image');

        $this->assertStringStartsWith('icons/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($svg, Storage::disk('public')->get($path));
        $this->assertDatabaseHas(Media::class, ['path' => $path, 'name' => 'WA Panitia']);
    }

    public function test_existing_icon_can_be_swapped_for_a_library_image(): void
    {
        $button = FloatingButton::factory()->create(['icon_image' => 'icons/lama.png']);
        $media = Media::factory()->create(['path' => 'media/telepon.png', 'mime_type' => 'image/png']);

        Livewire::test(EditFloatingButton::class, ['record' => $button->id])
            ->fillForm([
                'icon_image_source' => 'library',
                'icon_image_library' => $media->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('media/telepon.png', $button->fresh()->icon_image);
    }
}
