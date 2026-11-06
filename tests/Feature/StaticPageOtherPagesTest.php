<?php

namespace Tests\Feature;

use App\Http\Controllers\StaticPageController;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kotak "Halaman Lainnya" di sidebar dibatasi jumlahnya mengikuti urutan halaman,
 * dan bagian "Informasi Lainnya" di bawah halaman sudah tidak ada.
 */
class StaticPageOtherPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_lists_other_pages_up_to_the_limit_in_order(): void
    {
        $page = StaticPage::factory()->create(['show_sidebar' => true, 'sort_order' => 0]);

        $others = collect(range(1, StaticPageController::OTHER_PAGES_LIMIT + 3))
            ->map(fn (int $order): StaticPage => StaticPage::factory()->create([
                'title' => "Halaman Urutan {$order}",
                'slug' => "halaman-urutan-{$order}",
                'sort_order' => $order,
            ]));

        $response = $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Halaman Lainnya');

        $response->assertSeeInOrder(
            $others->take(StaticPageController::OTHER_PAGES_LIMIT)->pluck('title')->all()
        );

        foreach ($others->skip(StaticPageController::OTHER_PAGES_LIMIT) as $hidden) {
            $response->assertDontSee($hidden->title);
        }
    }

    public function test_sidebar_excludes_inactive_pages(): void
    {
        $page = StaticPage::factory()->create(['show_sidebar' => true]);
        $active = StaticPage::factory()->create(['title' => 'Halaman Aktif']);
        $inactive = StaticPage::factory()->create(['title' => 'Halaman Nonaktif', 'is_active' => false]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee($inactive->title);
    }

    public function test_sidebar_box_is_hidden_when_there_are_no_other_pages(): void
    {
        $page = StaticPage::factory()->create(['show_sidebar' => true]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertDontSee('Halaman Lainnya');
    }

    public function test_bottom_other_information_strip_is_not_rendered(): void
    {
        $page = StaticPage::factory()->create(['show_sidebar' => false]);
        StaticPage::factory()->count(2)->create();

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertDontSee('Informasi Lainnya');
    }
}
