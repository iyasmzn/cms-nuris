<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Teacher;
use App\Services\SitemapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Render the sitemap through a real request so URLs use the request host.
     */
    private function sitemapXml(): string
    {
        return $this->get(route('sitemap'))->assertOk()->getContent();
    }

    /**
     * Every `<loc>` in the sitemap, reduced to a request path.
     *
     * @return list<string>
     */
    private function sitemapPaths(): array
    {
        preg_match_all('#<loc>(.*?)</loc>#', $this->sitemapXml(), $matches);

        return array_map(
            fn (string $url): string => parse_url(html_entity_decode($url), PHP_URL_PATH) ?: '/',
            $matches[1],
        );
    }

    /**
     * The guard that matters: a listed URL that 404s or redirects is reported as
     * an indexing failure in Search Console, so nothing but a 200 may be listed.
     */
    public function test_every_listed_url_responds_with_200(): void
    {
        Post::factory()->create();
        Institution::factory()->create();

        $paths = $this->sitemapPaths();
        $this->assertNotEmpty($paths);

        $broken = [];

        foreach ($paths as $path) {
            $status = $this->get($path)->getStatusCode();

            if ($status !== 200) {
                $broken[$path] = $status;
            }
        }

        $this->assertSame([], $broken, 'URL sitemap yang tidak menjawab 200: '.json_encode($broken));
    }

    public function test_contact_page_is_listed(): void
    {
        $this->assertContains('/kontak', $this->sitemapPaths());
    }

    /**
     * `/ppdb` redirects to the only jenjang when just one is active, so listing
     * it would put a redirecting URL in the sitemap.
     */
    public function test_ppdb_index_is_omitted_when_a_single_jenjang_is_active(): void
    {
        $institution = Institution::factory()->create();

        $paths = $this->sitemapPaths();

        $this->assertNotContains('/ppdb', $paths);
        $this->assertContains("/ppdb/{$institution->slug}", $paths);
    }

    public function test_ppdb_index_is_listed_when_several_jenjang_are_active(): void
    {
        $first = Institution::factory()->create();
        $second = Institution::factory()->create();

        $paths = $this->sitemapPaths();

        $this->assertContains('/ppdb', $paths);
        $this->assertContains("/ppdb/{$first->slug}", $paths);
        $this->assertContains("/ppdb/{$second->slug}", $paths);
    }

    public function test_inactive_jenjang_is_omitted(): void
    {
        $institution = Institution::factory()->inactive()->create();

        $this->assertNotContains("/ppdb/{$institution->slug}", $this->sitemapPaths());
    }

    /**
     * Individual staff profiles are deliberately kept out of search results.
     */
    public function test_teacher_detail_pages_are_not_listed(): void
    {
        $teacher = Teacher::factory()->create();

        $paths = $this->sitemapPaths();

        $this->assertContains('/guru', $paths);
        $this->assertNotContains("/guru/{$teacher->getKey()}", $paths);
    }

    public function test_unpublished_post_is_omitted(): void
    {
        $post = Post::factory()->draft()->create();

        $this->assertNotContains("/blog/{$post->slug}", $this->sitemapPaths());
    }

    public function test_saving_content_invalidates_the_cached_sitemap(): void
    {
        $this->sitemapXml();
        $this->assertTrue(Cache::has(SitemapBuilder::CACHE_KEY));

        Post::factory()->create();

        $this->assertFalse(Cache::has(SitemapBuilder::CACHE_KEY));
    }

    public function test_saving_a_jenjang_invalidates_the_cached_sitemap(): void
    {
        $this->sitemapXml();

        Institution::factory()->create();

        $this->assertFalse(Cache::has(SitemapBuilder::CACHE_KEY));
    }

    /**
     * Settings decide which pages are reachable, and no content model is touched
     * when one changes, so the cache must be dropped on a setting write too.
     */
    public function test_changing_a_setting_invalidates_the_cached_sitemap(): void
    {
        $this->sitemapXml();

        Setting::set('feature_login_register', false);

        $this->assertFalse(Cache::has(SitemapBuilder::CACHE_KEY));
    }

    public function test_robots_txt_points_at_an_absolute_sitemap_url(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Sitemap: '.route('sitemap'), escape: false);
        $this->assertStringContainsString('http', route('sitemap'));
    }

    public function test_robots_txt_keeps_crawlers_off_download_streams(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Disallow: /unduhan/*/download', escape: false);
    }
}
