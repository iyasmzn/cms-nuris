<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AlumniUniversities\Pages\CreateAlumniUniversity;
use App\Filament\Resources\AlumniUniversities\Pages\EditAlumniUniversity;
use App\Filament\Resources\AlumniUniversities\Pages\ListAlumniUniversities;
use App\Models\AlumniUniversity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlumniUniversityResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->panelUser('AlumniUniversity');
        $this->actingAs($this->user);
    }

    // ── List ──────────────────────────────────────────────────────

    public function test_list_page_can_render(): void
    {
        $universities = AlumniUniversity::factory()->count(3)->create();

        Livewire::test(ListAlumniUniversities::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($universities);
    }

    public function test_list_page_can_search(): void
    {
        $visible = AlumniUniversity::factory()->create(['name' => 'Universitas Gadjah Mada']);
        $hidden = AlumniUniversity::factory()->create(['name' => 'Institut Teknologi Bandung']);

        Livewire::test(ListAlumniUniversities::class)
            ->searchTable('Gadjah')
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    // ── Create ────────────────────────────────────────────────────

    public function test_create_page_can_render(): void
    {
        Livewire::test(CreateAlumniUniversity::class)
            ->assertSuccessful();
    }

    public function test_can_create_alumni_university(): void
    {
        Livewire::test(CreateAlumniUniversity::class)
            ->fillForm([
                'name' => 'Universitas Indonesia',
                'url' => 'https://www.ui.ac.id',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(AlumniUniversity::class, [
            'name' => 'Universitas Indonesia',
            'url' => 'https://www.ui.ac.id',
            'is_active' => true,
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        Livewire::test(CreateAlumniUniversity::class)
            ->fillForm([
                'name' => null,
                'url' => 'bukan-url',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'url' => 'url',
            ])
            ->assertNotNotified();
    }

    // ── Edit ──────────────────────────────────────────────────────

    public function test_edit_page_can_render(): void
    {
        $university = AlumniUniversity::factory()->create();

        Livewire::test(EditAlumniUniversity::class, ['record' => $university->id])
            ->assertSuccessful()
            ->assertFormSet(['name' => $university->name]);
    }

    public function test_can_edit_alumni_university(): void
    {
        $university = AlumniUniversity::factory()->create(['is_active' => true]);

        Livewire::test(EditAlumniUniversity::class, ['record' => $university->id])
            ->fillForm(['name' => 'Universitas Diperbarui', 'is_active' => false])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(AlumniUniversity::class, [
            'id' => $university->id,
            'name' => 'Universitas Diperbarui',
            'is_active' => false,
        ]);
    }

    // ── Model / Factory ───────────────────────────────────────────

    public function test_active_scope_returns_only_active_in_sort_order(): void
    {
        $second = AlumniUniversity::factory()->create(['is_active' => true, 'sort_order' => 2]);
        $first = AlumniUniversity::factory()->create(['is_active' => true, 'sort_order' => 1]);
        AlumniUniversity::factory()->create(['is_active' => false, 'sort_order' => 0]);

        $active = AlumniUniversity::active()->get();

        $this->assertCount(2, $active);
        $this->assertSame([$first->id, $second->id], $active->pluck('id')->all());
    }

    public function test_initials_fall_back_to_the_name_when_no_logo_is_uploaded(): void
    {
        $university = AlumniUniversity::factory()->create([
            'name' => 'Universitas Gadjah Mada',
            'logo' => null,
        ]);

        $this->assertNull($university->logo_url);
        $this->assertSame('UGM', $university->initials);
    }
}
