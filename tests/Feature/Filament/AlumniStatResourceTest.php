<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AlumniStats\Pages\CreateAlumniStat;
use App\Filament\Resources\AlumniStats\Pages\EditAlumniStat;
use App\Filament\Resources\AlumniStats\Pages\ListAlumniStats;
use App\Models\AlumniStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlumniStatResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->panelUser('AlumniStat');
        $this->actingAs($this->user);
    }

    // ── List ──────────────────────────────────────────────────────

    public function test_list_page_can_render(): void
    {
        $alumniStats = AlumniStat::factory()->count(3)->create();

        Livewire::test(ListAlumniStats::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($alumniStats);
    }

    public function test_list_page_can_search(): void
    {
        $visible = AlumniStat::factory()->create(['label' => 'Alumni Terdata']);
        $hidden = AlumniStat::factory()->create(['label' => 'Kuliah Luar Negeri']);

        Livewire::test(ListAlumniStats::class)
            ->searchTable('Terdata')
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    // ── Create ────────────────────────────────────────────────────

    public function test_create_page_can_render(): void
    {
        Livewire::test(CreateAlumniStat::class)
            ->assertSuccessful();
    }

    public function test_can_create_alumni_stat(): void
    {
        Livewire::test(CreateAlumniStat::class)
            ->fillForm([
                'icon' => '🎓',
                'value' => '3.500+',
                'label' => 'Alumni Terdata',
                'sub' => 'Sejak angkatan pertama',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(AlumniStat::class, [
            'value' => '3.500+',
            'label' => 'Alumni Terdata',
            'sort_order' => 1,
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        Livewire::test(CreateAlumniStat::class)
            ->fillForm([
                'icon' => null,
                'value' => null,
                'label' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'icon' => 'required',
                'value' => 'required',
                'label' => 'required',
            ])
            ->assertNotNotified();
    }

    // ── Edit ──────────────────────────────────────────────────────

    public function test_edit_page_can_render(): void
    {
        $alumniStat = AlumniStat::factory()->create();

        Livewire::test(EditAlumniStat::class, ['record' => $alumniStat->id])
            ->assertSuccessful()
            ->assertFormSet([
                'label' => $alumniStat->label,
                'value' => $alumniStat->value,
            ]);
    }

    public function test_can_edit_alumni_stat(): void
    {
        $alumniStat = AlumniStat::factory()->create();

        Livewire::test(EditAlumniStat::class, ['record' => $alumniStat->id])
            ->fillForm(['label' => 'Label Diperbarui', 'value' => '99%'])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(AlumniStat::class, [
            'id' => $alumniStat->id,
            'label' => 'Label Diperbarui',
            'value' => '99%',
        ]);
    }

    // ── Model / Factory ───────────────────────────────────────────

    public function test_ordered_scope_sorts_by_sort_order(): void
    {
        $second = AlumniStat::factory()->create(['sort_order' => 2]);
        $first = AlumniStat::factory()->create(['sort_order' => 1]);

        $this->assertSame(
            [$first->id, $second->id],
            AlumniStat::ordered()->get()->pluck('id')->all(),
        );
    }
}
