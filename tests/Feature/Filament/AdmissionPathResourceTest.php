<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AdmissionPaths\Pages\CreateAdmissionPath;
use App\Filament\Resources\AdmissionPaths\Pages\EditAdmissionPath;
use App\Models\AdmissionPath;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdmissionPathResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->panelUser('AdmissionPath'));
    }

    public function test_it_creates_a_path_with_a_detail_link(): void
    {
        Livewire::test(CreateAdmissionPath::class)
            ->fillForm([
                'name' => 'Beasiswa Unggulan',
                'slug' => 'beasiswa-unggulan',
                'color' => 'primary',
                'detail_url' => '/halaman/beasiswa-unggulan',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(AdmissionPath::class, [
            'slug' => 'beasiswa-unggulan',
            'detail_url' => '/halaman/beasiswa-unggulan',
        ]);
    }

    public function test_detail_link_must_look_like_a_link(): void
    {
        $path = AdmissionPath::factory()->create();

        Livewire::test(EditAdmissionPath::class, ['record' => $path->id])
            ->fillForm(['detail_url' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasFormErrors(['detail_url' => 'regex']);
    }

    public function test_uploaded_icon_is_added_to_the_media_library(): void
    {
        Storage::fake('public');

        Livewire::test(CreateAdmissionPath::class)
            ->fillForm([
                'name' => 'Beasiswa Unggulan',
                'slug' => 'beasiswa-unggulan',
                'color' => 'primary',
                'icon_image' => UploadedFile::fake()->createWithContent('medali.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $path = AdmissionPath::query()->where('slug', 'beasiswa-unggulan')->value('icon_image');

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas(Media::class, ['path' => $path, 'name' => 'Beasiswa Unggulan']);
    }

    public function test_icon_can_be_picked_from_the_media_library(): void
    {
        $admissionPath = AdmissionPath::factory()->create();
        $media = Media::factory()->create(['path' => 'media/medali.png', 'mime_type' => 'image/png']);

        Livewire::test(EditAdmissionPath::class, ['record' => $admissionPath->id])
            ->fillForm([
                'icon_image_source' => 'library',
                'icon_image_library' => $media->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('media/medali.png', $admissionPath->fresh()->icon_image);
    }
}
