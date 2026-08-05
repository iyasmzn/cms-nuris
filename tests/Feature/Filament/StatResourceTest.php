<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Stats\Pages\CreateStat;
use App\Filament\Resources\Stats\Pages\EditStat;
use App\Models\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->panelUser('Stat'));
    }

    public function test_can_create_a_stat_with_a_card_link(): void
    {
        Livewire::test(CreateStat::class)
            ->fillForm([
                'icon' => '🏆',
                'value' => '200+',
                'label' => 'Prestasi',
                'url' => '/prestasi',
                'url_new_tab' => false,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Stat::class, [
            'label' => 'Prestasi',
            'url' => '/prestasi',
            'url_new_tab' => false,
        ]);
    }

    public function test_can_create_a_stat_that_opens_its_link_in_a_new_tab(): void
    {
        Livewire::test(CreateStat::class)
            ->fillForm([
                'icon' => '📜',
                'value' => 'A',
                'label' => 'Akreditasi',
                'url' => 'https://banpdm.kemdikbud.go.id',
                'url_new_tab' => true,
                'sort_order' => 2,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Stat::class, [
            'label' => 'Akreditasi',
            'url' => 'https://banpdm.kemdikbud.go.id',
            'url_new_tab' => true,
        ]);
    }

    public function test_the_new_tab_toggle_is_hidden_until_a_link_is_filled(): void
    {
        Livewire::test(CreateStat::class)
            ->assertFormFieldHidden('url_new_tab')
            ->fillForm(['url' => '/prestasi'])
            ->assertFormFieldVisible('url_new_tab');
    }

    public function test_the_card_link_is_optional(): void
    {
        Livewire::test(CreateStat::class)
            ->fillForm([
                'icon' => '🎓',
                'value' => '850',
                'label' => 'Santri Aktif',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Stat::class, [
            'label' => 'Santri Aktif',
            'url' => null,
        ]);
    }

    public function test_it_rejects_a_link_without_a_recognised_scheme(): void
    {
        Livewire::test(CreateStat::class)
            ->fillForm([
                'icon' => '🏫',
                'value' => '12',
                'label' => 'Gedung',
                'url' => 'prestasi.html',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['url']);
    }

    public function test_can_change_the_card_link_of_an_existing_stat(): void
    {
        $stat = Stat::factory()->create(['url' => null]);

        Livewire::test(EditStat::class, ['record' => $stat->id])
            ->fillForm(['url' => 'https://sekolah.test/akreditasi'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://sekolah.test/akreditasi', $stat->fresh()->url);
    }
}
