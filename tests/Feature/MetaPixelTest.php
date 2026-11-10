<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionPath;
use App\Models\Institution;
use App\Models\RegistrationWave;
use App\Models\Setting;
use App\Models\SpmbRegistration;
use App\Support\MetaPixel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MetaPixelTest extends TestCase
{
    use RefreshDatabase;

    private const PIXEL_ID = '1234567890123456';

    private const FBC = 'fb.1.1730000000000.IwAR2F4-dbP0l7Mn1IawQ_qa2ofrHyiLjcbCRxTDMgk';

    private const FBP = 'fb.1.1730000000000.1098115397';

    private Institution $institution;

    private AdmissionPath $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'SMP Nurul Islam',
            'slug' => 'smp',
            'short_name' => 'SMP',
        ]);
        RegistrationWave::factory()->open()->create([
            'academic_year_id' => AcademicYear::factory()->active()->create()->id,
            'institution_id' => $this->institution->id,
        ]);
        $this->path = AdmissionPath::firstOrCreate(
            ['slug' => 'zonasi'],
            ['name' => 'Zonasi', 'is_active' => true],
        );

        Setting::set('spmb_form_enabled', '1');
    }

    private function enablePixel(string $id = self::PIXEL_ID): void
    {
        Setting::setMany([
            'meta_pixel_enabled' => '1',
            'meta_pixel_id' => $id,
        ]);
    }

    /**
     * Make the jenjang charge a fee, so a stored registration is sent to the
     * signed status page instead of back to the PPDB page.
     */
    private function chargeRegistrationFee(): void
    {
        $this->institution->update(['registration_fee' => 150_000]);

        Setting::setMany([
            'spmb_payment_enabled' => '1',
            'spmb_bank_accounts' => json_encode([
                ['bank' => 'BSI', 'number' => '7123456789', 'holder' => 'Yayasan Nurul Islam'],
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submitRegistration(array $overrides = []): TestResponse
    {
        return $this->post(route('ppdb.store', $this->institution), array_merge([
            'full_name' => 'Budi Santoso',
            'nik' => '3273010101080001',
            'phone' => '081234567890',
            'previous_school' => 'SMP Negeri 1',
            'admission_path_id' => $this->path->id,
        ], $overrides));
    }

    // ── Saklar ───────────────────────────────────────────────────────

    public function test_no_pixel_is_rendered_while_it_is_disabled(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('fbevents.js', escape: false)
            ->assertDontSee('facebook.com/tr', escape: false);
    }

    public function test_the_pixel_stays_off_when_enabled_without_an_id(): void
    {
        Setting::set('meta_pixel_enabled', '1');

        $this->assertFalse(MetaPixel::enabled());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('fbevents.js', escape: false);
    }

    public function test_a_saved_id_does_nothing_while_the_switch_is_off(): void
    {
        Setting::setMany([
            'meta_pixel_enabled' => '0',
            'meta_pixel_id' => self::PIXEL_ID,
        ]);

        $this->assertFalse(MetaPixel::enabled());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('fbevents.js', escape: false);
    }

    public function test_a_malformed_pixel_id_is_discarded(): void
    {
        $this->enablePixel("1');alert(1);//");

        $this->assertNull(MetaPixel::pixelId());
        $this->assertFalse(MetaPixel::enabled());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('fbevents.js', escape: false)
            ->assertDontSee('alert(1)', escape: false);
    }

    public function test_surrounding_whitespace_is_ignored_in_the_pixel_id(): void
    {
        $this->enablePixel('  '.self::PIXEL_ID.' ');

        $this->assertSame(self::PIXEL_ID, MetaPixel::pixelId());
    }

    // ── PageView di halaman publik ───────────────────────────────────

    public function test_the_homepage_loads_the_pixel_and_records_a_page_view(): void
    {
        $this->enablePixel();

        $response = $this->get('/')->assertOk();

        $response->assertSee('https://connect.facebook.net/en_US/fbevents.js', escape: false);
        $response->assertSee("fbq('init', '".self::PIXEL_ID."');", escape: false);
        $response->assertSee("fbq('track', 'PageView');", escape: false);
        $response->assertSee('https://www.facebook.com/tr?id='.self::PIXEL_ID.'&amp;ev=PageView&amp;noscript=1', escape: false);
    }

    public function test_a_page_on_the_shared_public_layout_loads_the_pixel(): void
    {
        $this->enablePixel();

        $response = $this->get(route('ppdb.show', $this->institution))->assertOk();

        $response->assertSee("fbq('init', '".self::PIXEL_ID."');", escape: false);
        $response->assertSee("fbq('track', 'PageView');", escape: false);
        $response->assertSee('ev=PageView&amp;noscript=1', escape: false);
    }

    /**
     * Without autoConfig switched off the pixel records button clicks and page
     * contents by itself, on pages that carry the formulir PPDB.
     */
    public function test_automatic_configuration_is_switched_off_before_init(): void
    {
        $this->enablePixel();

        $this->get('/')->assertOk()->assertSeeInOrder([
            "fbq('set', 'autoConfig', false, '".self::PIXEL_ID."');",
            "fbq('init', '".self::PIXEL_ID."');",
        ], escape: false);
    }

    public function test_whatsapp_chat_clicks_are_reported_as_contact(): void
    {
        $this->enablePixel();

        $this->get('/')
            ->assertOk()
            ->assertSee("fbq('track', 'Contact', {content_category: 'WhatsApp'});", escape: false);
    }

    // ── Pengecualian halaman bertanda tangan ─────────────────────────

    public function test_the_signed_ppdb_status_page_never_loads_the_pixel(): void
    {
        $this->enablePixel();

        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);

        $response = $this->get(URL::signedRoute('ppdb.payment', $registration))->assertOk();

        // URL halaman ini adalah kredensialnya; script pixel mengirim URL
        // lengkap ke Meta, jadi tidak dimuat sama sekali di sini.
        $response->assertSee('name="referrer"', escape: false);
        $response->assertDontSee('fbevents.js', escape: false);
        $response->assertDontSee('facebook.com/tr', escape: false);
    }

    // ── Lead saat pendaftaran PPDB tersimpan ─────────────────────────

    public function test_a_stored_registration_is_reported_as_a_lead_on_the_ppdb_page(): void
    {
        $this->enablePixel();

        $response = $this->submitRegistration();
        $response->assertRedirect(route('ppdb.show', $this->institution));

        $this->get(route('ppdb.show', $this->institution))
            ->assertOk()
            ->assertSeeInOrder([
                "fbq('track', 'PageView');",
                'fbq(\'track\', "Lead", {"content_name":"SMP Nurul Islam","content_category":"PPDB"});',
            ], escape: false);
    }

    public function test_the_lead_is_reported_once_and_not_again_on_reload(): void
    {
        $this->enablePixel();

        $this->submitRegistration();

        $this->get(route('ppdb.show', $this->institution))->assertSee('"Lead"', escape: false);
        $this->get(route('ppdb.show', $this->institution))->assertDontSee('"Lead"', escape: false);
    }

    public function test_the_lead_carries_none_of_the_formulir_data(): void
    {
        $this->enablePixel();

        $this->submitRegistration();

        $registration = SpmbRegistration::firstWhere('nik', '3273010101080001');
        $this->assertNotNull($registration);

        $events = MetaPixel::queuedEvents();

        $this->assertCount(1, $events);
        $this->assertSame('Lead', $events[0]['name']);
        $this->assertSame(['content_name' => 'SMP Nurul Islam', 'content_category' => 'PPDB'], $events[0]['parameters']);
        $this->assertSame(route('ppdb.show', $this->institution), $events[0]['page_url']);

        $queued = json_encode($events);

        foreach (['Budi Santoso', '3273010101080001', '081234567890', $registration->registration_number] as $personalData) {
            $this->assertStringNotContainsString($personalData, $queued);
        }
    }

    public function test_no_lead_is_queued_while_the_pixel_is_disabled(): void
    {
        $this->submitRegistration()->assertSessionHas('success');

        $this->assertSame([], MetaPixel::queuedEvents());
    }

    public function test_a_rejected_registration_is_not_a_lead(): void
    {
        $this->enablePixel();

        $this->submitRegistration(['full_name' => ''])->assertSessionHasErrors('full_name');

        $this->assertSame([], MetaPixel::queuedEvents());
    }

    public function test_a_lead_redirected_to_the_signed_status_page_is_sent_without_its_url(): void
    {
        $this->enablePixel();
        $this->chargeRegistrationFee();

        $statusUrl = $this->submitRegistration()->assertRedirect()->headers->get('Location');
        $this->assertStringContainsString('signature=', $statusUrl);

        $response = $this->get($statusUrl)->assertOk();

        // Tetap tanpa script pixel; Lead dikirim lewat gambar 1px yang membawa
        // URL halaman PPDB jenjangnya, bukan URL bertanda tangan ini.
        $response->assertDontSee('fbevents.js', escape: false);
        $response->assertSee('referrerpolicy="no-referrer"', escape: false);

        $source = $this->imagePixelSource($response);
        parse_str((string) parse_url($source, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://www.facebook.com/tr?', $source);
        $this->assertSame(self::PIXEL_ID, $query['id']);
        $this->assertSame('Lead', $query['ev']);
        $this->assertSame(route('ppdb.show', $this->institution), $query['dl']);
        $this->assertSame(['content_name' => 'SMP Nurul Islam', 'content_category' => 'PPDB'], $query['cd']);

        $path = (string) parse_url($statusUrl, PHP_URL_PATH);
        $this->assertStringNotContainsString($path, urldecode($source));
        $this->assertStringNotContainsString('signature', $source);
    }

    public function test_the_status_page_lead_forwards_the_ad_click_cookies(): void
    {
        $this->enablePixel();
        $this->chargeRegistrationFee();

        $statusUrl = $this->submitRegistration()->headers->get('Location');

        $response = $this->withUnencryptedCookies(['_fbc' => self::FBC, '_fbp' => self::FBP])
            ->get($statusUrl)
            ->assertOk();

        parse_str((string) parse_url($this->imagePixelSource($response), PHP_URL_QUERY), $query);

        $this->assertSame(self::FBC, $query['fbc']);
        $this->assertSame(self::FBP, $query['fbp']);
    }

    public function test_malformed_ad_click_cookies_are_not_forwarded(): void
    {
        $this->enablePixel();
        $this->chargeRegistrationFee();

        $statusUrl = $this->submitRegistration()->headers->get('Location');

        $response = $this->withUnencryptedCookies(['_fbc' => '"><script>alert(1)</script>', '_fbp' => 'not-a-meta-cookie'])
            ->get($statusUrl)
            ->assertOk();

        parse_str((string) parse_url($this->imagePixelSource($response), PHP_URL_QUERY), $query);

        $this->assertArrayNotHasKey('fbc', $query);
        $this->assertArrayNotHasKey('fbp', $query);
        $response->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    public function test_the_status_page_sends_the_lead_only_once(): void
    {
        $this->enablePixel();
        $this->chargeRegistrationFee();

        $statusUrl = $this->submitRegistration()->headers->get('Location');

        $this->get($statusUrl)->assertSee('facebook.com/tr?', escape: false);
        $this->get($statusUrl)->assertDontSee('facebook.com/tr?', escape: false);
    }

    /**
     * The decoded `src` of the image pixel the status page renders.
     */
    private function imagePixelSource(TestResponse $response): string
    {
        $matched = preg_match('/<img[^>]+src="(https:\/\/www\.facebook\.com\/tr\?[^"]+)"/', (string) $response->getContent(), $match);

        $this->assertSame(1, $matched, 'The status page renders no image pixel.');

        return html_entity_decode($match[1]);
    }
}
