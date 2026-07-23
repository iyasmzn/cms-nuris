<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_register_is_enabled_by_default(): void
    {
        $this->assertTrue(feature_enabled('login_register'));
    }

    public function test_disabling_login_register_returns_not_found_for_auth_pages(): void
    {
        Setting::set('feature_login_register', false);

        $this->get(route('login'))->assertNotFound();
        $this->get(route('register'))->assertNotFound();
    }
}
