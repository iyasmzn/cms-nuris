<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Models\Media;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->panelUser('Program'));
    }

    public function test_uploaded_icon_is_added_to_the_media_library(): void
    {
        Storage::fake('public');

        Livewire::test(CreateProgram::class)
            ->fillForm([
                'title' => 'Tahfidz Quran',
                'slug' => 'tahfidz-quran',
                'icon_image' => UploadedFile::fake()->createWithContent('quran.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $path = Program::query()->where('slug', 'tahfidz-quran')->value('icon_image');

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas(Media::class, ['path' => $path, 'name' => 'Tahfidz Quran']);
    }

    public function test_icon_can_be_picked_from_the_media_library(): void
    {
        $program = Program::factory()->create(['blocks' => [], 'hero' => null, 'category' => null]);
        $media = Media::factory()->create(['path' => 'media/quran.png', 'mime_type' => 'image/png']);

        Livewire::test(EditProgram::class, ['record' => $program->id])
            ->fillForm([
                'icon_image_source' => 'library',
                'icon_image_library' => $media->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('media/quran.png', $program->fresh()->icon_image);
    }
}
