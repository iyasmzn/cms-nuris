<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarMenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    private function setNavWithSubmenu(array $children): void
    {
        Setting::set('nav_items', json_encode([
            ['label' => 'Beranda', 'url' => '/', 'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'Akademik', 'url' => '#akademik', 'target' => '_self', 'is_active' => true, 'children' => $children],
        ]));
    }

    public function test_submenu_icon_and_description_are_rendered(): void
    {
        $this->setNavWithSubmenu([
            [
                'label' => 'Kurikulum',
                'url' => '#kurikulum',
                'target' => '_self',
                'is_active' => true,
                'icon' => '📘',
                'description' => 'Struktur & jadwal pelajaran',
            ],
        ]);

        $response = $this->get(route('home'))->assertOk();

        // Desktop dropdown and the mobile overlay both render the pair.
        $this->assertSame(2, substr_count($response->getContent(), '📘'));
        $this->assertSame(2, substr_count($response->getContent(), 'Struktur &amp; jadwal pelajaran'));
    }

    /**
     * A roomier panel is only worth it once there is something beside the label
     * to make room for.
     */
    public function test_the_panel_only_widens_for_a_submenu_that_has_icons_or_descriptions(): void
    {
        $this->setNavWithSubmenu([
            ['label' => 'Kurikulum', 'url' => '#kurikulum', 'target' => '_self', 'is_active' => true],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('min-w-48', false)
            ->assertDontSee('min-w-64', false);

        $this->setNavWithSubmenu([
            [
                'label' => 'Kurikulum',
                'url' => '#kurikulum',
                'target' => '_self',
                'is_active' => true,
                'description' => 'Struktur & jadwal pelajaran',
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('min-w-64', false);
    }

    /**
     * The dropdown must be reachable without a mouse: the trigger announces the
     * panel it owns and whether it is open, and the panel answers the keyboard.
     */
    public function test_the_dropdown_trigger_carries_its_accessibility_wiring(): void
    {
        $this->setNavWithSubmenu([
            ['label' => 'Kurikulum', 'url' => '#kurikulum', 'target' => '_self', 'is_active' => true],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-haspopup="true"', false)
            ->assertSee('aria-controls="nav-drop-1"', false)
            ->assertSee(':aria-expanded="dropOpen"', false)
            ->assertSee('id="nav-drop-1"', false)
            ->assertSee('@keydown.escape.prevent="dismiss()"', false)
            ->assertSee('@keydown.down.prevent="moveFocus(1)"', false);
    }

    public function test_an_inactive_submenu_entry_is_not_rendered(): void
    {
        $this->setNavWithSubmenu([
            ['label' => 'Kurikulum', 'url' => '#kurikulum', 'target' => '_self', 'is_active' => true],
            ['label' => 'Rahasia', 'url' => '#rahasia', 'target' => '_self', 'is_active' => false],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kurikulum')
            ->assertDontSee('Rahasia');
    }

    /**
     * Regression: a dropdown whose URL is a bare `#` (or `/`) names no
     * destination — it only opens its menu. Both used to resolve to "the current
     * page, no section", so on the landing page every dropdown lit up at once
     * alongside Beranda.
     */
    public function test_a_dropdown_that_merely_opens_a_menu_does_not_claim_the_home_highlight(): void
    {
        Setting::set('nav_items', json_encode([
            ['label' => 'Beranda', 'url' => '/', 'target' => '_self', 'is_active' => true, 'children' => []],
            ['label' => 'Profil', 'url' => '#', 'target' => '_self', 'is_active' => true, 'children' => [
                ['label' => 'Sambutan', 'url' => '#sambutan', 'target' => '_self', 'is_active' => true],
            ]],
            ['label' => 'Akademik', 'url' => '/', 'target' => '_self', 'is_active' => true, 'children' => [
                ['label' => 'Kurikulum', 'url' => '/kurikulum', 'target' => '_self', 'is_active' => true],
            ]],
        ]));

        $html = $this->get(route('home'))->assertOk()->getContent();

        $groups = $this->renderedSpyGroups($html);

        $this->assertNotEmpty($groups, 'Tidak ada dropdown yang terender.');

        foreach ($groups as $spies) {
            $this->assertNotContains('', $spies, 'Dropdown tidak boleh ikut menandai "halaman ini" saat tak ada seksi yang terlihat.');
        }

        // Beranda tetap pemilik sah penanda itu: spy-nya string kosong.
        $this->assertStringContainsString("{ spy: '' }", $html);
    }

    /**
     * A dropdown that points at a real section still highlights with it.
     */
    public function test_a_dropdown_pointing_at_a_section_keeps_its_spy(): void
    {
        $this->setNavWithSubmenu([
            ['label' => 'Sambutan', 'url' => '#sambutan', 'target' => '_self', 'is_active' => true],
        ]);

        $groups = $this->renderedSpyGroups($this->get(route('home'))->assertOk()->getContent());

        $this->assertNotEmpty($groups);

        foreach ($groups as $spies) {
            $this->assertContains('akademik', $spies);
            $this->assertContains('sambutan', $spies);
        }
    }

    /**
     * The spy keys each dropdown was rendered with, decoded from its Alpine state.
     *
     * @return array<int, array<int, ?string>>
     */
    private function renderedSpyGroups(string $html): array
    {
        preg_match_all("/spies: JSON\.parse\('(.*?)'\)/", $html, $matches);

        return array_map(
            fn (string $json): array => json_decode(preg_replace('/\\\\u0022/', '"', $json), true),
            $matches[1],
        );
    }
}
