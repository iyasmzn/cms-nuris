<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AdmissionPaths\Pages\CreateAdmissionPath;
use App\Filament\Resources\AdmissionPaths\Pages\EditAdmissionPath;
use App\Models\AdmissionPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
