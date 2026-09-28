<?php

namespace Tests\Feature;

use App\Models\FloatingButton;
use App\Models\Institution;
use App\Models\Program;
use App\Models\SpmbRegistration;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Floating button bisa diarahkan ke halaman tertentu — kasus utamanya: tiap
 * jenjang PPDB punya tombol WA panitia sendiri.
 */
class FloatingButtonTargetingTest extends TestCase
{
    use RefreshDatabase;

    private Institution $sd;

    private Institution $smp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sd = Institution::factory()->create(['name' => 'SD', 'slug' => 'sd', 'short_name' => 'SD', 'sort_order' => 1]);
        $this->smp = Institution::factory()->create(['name' => 'SMP', 'slug' => 'smp', 'short_name' => 'SMP', 'sort_order' => 2]);
    }

    public function test_button_for_every_page_shows_everywhere(): void
    {
        FloatingButton::factory()->create(['label' => 'Tombol Umum']);

        $this->get(route('home'))->assertOk()->assertSee('Tombol Umum');
        $this->get(route('ppdb.show', $this->sd))->assertOk()->assertSee('Tombol Umum');
        $this->get(route('contact.index'))->assertOk()->assertSee('Tombol Umum');
    }

    public function test_each_jenjang_ppdb_page_shows_its_own_button(): void
    {
        FloatingButton::factory()->onlyOn(["ppdb:{$this->sd->id}"])->create(['label' => 'Tombol Panitia SD']);
        FloatingButton::factory()->onlyOn(["ppdb:{$this->smp->id}"])->create(['label' => 'Tombol Panitia SMP']);

        $this->get(route('ppdb.show', $this->sd))
            ->assertOk()
            ->assertSee('Tombol Panitia SD')
            ->assertDontSee('Tombol Panitia SMP');

        $this->get(route('ppdb.show', $this->smp))
            ->assertOk()
            ->assertSee('Tombol Panitia SMP')
            ->assertDontSee('Tombol Panitia SD');
    }

    public function test_jenjang_button_stays_off_other_pages(): void
    {
        FloatingButton::factory()->onlyOn(["ppdb:{$this->sd->id}"])->create(['label' => 'Tombol Panitia SD']);

        $this->get(route('home'))->assertOk()->assertDontSee('Tombol Panitia SD');
        $this->get(route('ppdb.index'))->assertOk()->assertDontSee('Tombol Panitia SD');
        $this->get(route('ppdb.status'))->assertOk()->assertDontSee('Tombol Panitia SD');
    }

    public function test_jenjang_button_follows_the_registrant_to_the_status_page(): void
    {
        FloatingButton::factory()->onlyOn(["ppdb:{$this->sd->id}"])->create(['label' => 'Tombol Panitia SD']);
        FloatingButton::factory()->onlyOn(["ppdb:{$this->smp->id}"])->create(['label' => 'Tombol Panitia SMP']);
        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->sd->id]);

        $this->get(URL::signedRoute('ppdb.payment', $registration))
            ->assertOk()
            ->assertSee('Tombol Panitia SD')
            ->assertDontSee('Tombol Panitia SMP');
    }

    public function test_general_button_can_be_hidden_on_every_ppdb_page(): void
    {
        FloatingButton::factory()->exceptOn(['group:ppdb'])->create(['label' => 'Tombol Umum']);

        $this->get(route('home'))->assertOk()->assertSee('Tombol Umum');
        $this->get(route('blog.index'))->assertOk()->assertSee('Tombol Umum');
        $this->get(route('ppdb.index'))->assertOk()->assertDontSee('Tombol Umum');
        $this->get(route('ppdb.show', $this->sd))->assertOk()->assertDontSee('Tombol Umum');
        $this->get(route('ppdb.status'))->assertOk()->assertDontSee('Tombol Umum');
    }

    public function test_general_button_can_be_hidden_on_one_jenjang_only(): void
    {
        FloatingButton::factory()->exceptOn(["ppdb:{$this->sd->id}"])->create(['label' => 'Tombol Umum']);

        $this->get(route('ppdb.show', $this->sd))->assertOk()->assertDontSee('Tombol Umum');
        $this->get(route('ppdb.show', $this->smp))->assertOk()->assertSee('Tombol Umum');
    }

    public function test_button_can_target_a_single_static_page(): void
    {
        $about = StaticPage::factory()->create(['slug' => 'tentang-kami']);
        $other = StaticPage::factory()->create(['slug' => 'visi-misi']);
        FloatingButton::factory()->onlyOn(["page:{$about->id}"])->create(['label' => 'Tombol Tentang Kami']);

        $this->get(route('page.show', $about->slug))->assertOk()->assertSee('Tombol Tentang Kami');
        $this->get(route('page.show', $other->slug))->assertOk()->assertDontSee('Tombol Tentang Kami');
    }

    public function test_static_page_target_survives_a_slug_change(): void
    {
        $about = StaticPage::factory()->create(['slug' => 'tentang-kami']);
        FloatingButton::factory()->onlyOn(["page:{$about->id}"])->create(['label' => 'Tombol Tentang Kami']);

        $about->update(['slug' => 'profil-sekolah']);

        $this->get(route('page.show', 'profil-sekolah'))->assertOk()->assertSee('Tombol Tentang Kami');
    }

    public function test_button_can_target_a_single_program(): void
    {
        $tahfidz = Program::factory()->create(['slug' => 'tahfidz']);
        $bahasa = Program::factory()->create(['slug' => 'bahasa']);
        FloatingButton::factory()->onlyOn(["program:{$tahfidz->id}"])->create(['label' => 'Tombol Tahfidz']);

        $this->get(route('programs.show', $tahfidz))->assertOk()->assertSee('Tombol Tahfidz');
        $this->get(route('programs.show', $bahasa))->assertOk()->assertDontSee('Tombol Tahfidz');
        $this->get(route('programs.index'))->assertOk()->assertDontSee('Tombol Tahfidz');
    }

    public function test_inactive_button_stays_hidden_on_its_target_page(): void
    {
        FloatingButton::factory()->inactive()->onlyOn(["ppdb:{$this->sd->id}"])->create(['label' => 'Tombol Panitia SD']);

        $this->get(route('ppdb.show', $this->sd))->assertOk()->assertDontSee('Tombol Panitia SD');
    }

    public function test_showing_on_every_page_clears_leftover_targets(): void
    {
        $button = FloatingButton::factory()->onlyOn(["ppdb:{$this->sd->id}"])->create();

        $button->update(['display_mode' => FloatingButton::DISPLAY_ALL]);

        $this->assertNull($button->fresh()->display_targets);
    }
}
