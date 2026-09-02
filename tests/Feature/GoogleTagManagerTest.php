<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\SpmbRegistration;
use App\Support\GoogleTagManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GoogleTagManagerTest extends TestCase
{
    use RefreshDatabase;

    private const CONTAINER_ID = 'GTM-ABC1234';

    private function enableGtm(string $id = self::CONTAINER_ID): void
    {
        Setting::setMany([
            'gtm_enabled' => '1',
            'gtm_container_id' => $id,
        ]);
    }

    // ── Saklar ───────────────────────────────────────────────────────

    public function test_no_gtm_script_is_rendered_when_the_container_is_disabled(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', escape: false);
    }

    public function test_gtm_stays_off_when_enabled_without_a_container_id(): void
    {
        Setting::set('gtm_enabled', '1');

        $this->assertFalse(GoogleTagManager::enabled());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', escape: false);
    }

    public function test_a_malformed_container_id_is_discarded(): void
    {
        $this->enableGtm('"><script>alert(1)</script>');

        $this->assertNull(GoogleTagManager::containerId());
        $this->assertFalse(GoogleTagManager::enabled());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', escape: false);
    }

    public function test_a_lowercase_container_id_is_normalised(): void
    {
        $this->enableGtm('  gtm-abc1234 ');

        $this->assertSame(self::CONTAINER_ID, GoogleTagManager::containerId());
    }

    // ── Pemasangan di halaman publik ─────────────────────────────────

    public function test_the_homepage_renders_both_gtm_snippets(): void
    {
        $this->enableGtm();

        $response = $this->get('/')->assertOk();

        $response->assertSee('googletagmanager.com/gtm.js?id=', escape: false);
        $response->assertSee("'".self::CONTAINER_ID."'", escape: false);
        $response->assertSee('googletagmanager.com/ns.html?id='.self::CONTAINER_ID, escape: false);
    }

    public function test_a_page_on_the_shared_public_layout_renders_both_gtm_snippets(): void
    {
        $this->enableGtm();

        $response = $this->get(route('ppdb.index'))->assertOk();

        $response->assertSee('googletagmanager.com/gtm.js?id=', escape: false);
        $response->assertSee('googletagmanager.com/ns.html?id='.self::CONTAINER_ID, escape: false);
    }

    // ── Pengecualian halaman bertanda tangan ─────────────────────────

    public function test_the_signed_ppdb_status_page_is_never_tracked(): void
    {
        $this->enableGtm();

        $registration = SpmbRegistration::factory()->create();

        $response = $this->get(URL::signedRoute('ppdb.payment', $registration))->assertOk();

        // URL halaman ini adalah kredensialnya; GTM mengirim URL lengkap ke
        // Google, jadi script-nya sengaja tidak dimuat di sini.
        $response->assertSee('name="referrer"', escape: false);
        $response->assertDontSee('googletagmanager.com', escape: false);
    }
}
