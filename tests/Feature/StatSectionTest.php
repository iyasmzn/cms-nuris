<?php

namespace Tests\Feature;

use App\Models\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kartu statistik halaman depan, khususnya tautan opsional yang membuat
 * seluruh kartunya bisa diklik.
 */
class StatSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stat_with_a_link_makes_the_whole_card_clickable(): void
    {
        Stat::factory()->create([
            'label' => 'Prestasi',
            'value' => '200+',
            'url' => '/prestasi',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="/prestasi"', false)
            ->assertSee('class="stat-card-link"', false);
    }

    public function test_a_stat_without_a_link_stays_a_plain_card(): void
    {
        Stat::factory()->create([
            'label' => 'Santri Aktif',
            'url' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Santri Aktif')
            ->assertDontSee('stat-card-link"', false);
    }

    public function test_the_toggle_decides_whether_the_link_opens_in_a_new_tab(): void
    {
        $stat = Stat::factory()->create([
            'label' => 'Akreditasi',
            'url' => 'https://banpdm.kemdikbud.go.id',
            'url_new_tab' => true,
        ]);

        $anchor = $this->statCardLink();

        $this->assertStringContainsString('href="https://banpdm.kemdikbud.go.id"', $anchor);
        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $anchor);

        // Saklar dimatikan: tautan ke situs lain pun tetap di tab yang sama
        $stat->update(['url_new_tab' => false]);

        $this->assertStringNotContainsString('target="_blank"', $this->statCardLink());
    }

    public function test_an_internal_path_can_also_open_in_a_new_tab(): void
    {
        Stat::factory()->create([
            'label' => 'Program',
            'url' => '/program',
            'url_new_tab' => true,
        ]);

        $anchor = $this->statCardLink();

        $this->assertStringContainsString('href="/program"', $anchor);
        $this->assertStringContainsString('target="_blank"', $anchor);
    }

    public function test_an_internal_path_stays_in_the_same_tab_by_default(): void
    {
        Stat::factory()->create([
            'label' => 'Profil',
            'url' => '#profil',
        ]);

        $anchor = $this->statCardLink();

        $this->assertStringContainsString('href="#profil"', $anchor);
        $this->assertStringNotContainsString('target="_blank"', $anchor);
    }

    /**
     * Tag <a> pembungkus kartu statistik, dirapatkan jadi satu baris agar
     * assertion tidak bergantung pada pembungkus baris di Blade.
     */
    private function statCardLink(): string
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<a[^>]*stat-card-link[^>]*>/s', $html);

        preg_match('/<a[^>]*stat-card-link[^>]*>/s', $html, $matches);

        return (string) preg_replace('/\s+/', ' ', $matches[0]);
    }

    public function test_link_helpers_follow_the_stored_url(): void
    {
        $withoutLink = new Stat(['url' => null, 'url_new_tab' => true]);
        $sameTab = new Stat(['url' => '/prestasi']);
        $newTab = new Stat(['url' => 'https://example.test', 'url_new_tab' => true]);

        // Saklar tab baru tidak berarti apa-apa tanpa tautan
        $this->assertFalse($withoutLink->has_link);
        $this->assertFalse($withoutLink->link_opens_in_new_tab);
        $this->assertTrue($sameTab->has_link);
        $this->assertFalse($sameTab->link_opens_in_new_tab);
        $this->assertTrue($newTab->has_link);
        $this->assertTrue($newTab->link_opens_in_new_tab);
    }
}
