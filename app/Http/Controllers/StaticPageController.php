<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use App\Support\LandingBlocks;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = StaticPage::active()
            ->where('slug', $slug)
            ->firstOrFail();

        // Blok data (Statistik, Program, Berita, …) dibekali isinya; yang kosong dibuang
        $blocks = LandingBlocks::prepare($page->blocks);

        $seo = [
            'title' => $page->title.' | '.setting('site_name', config('app.name')),
            'description' => $page->seoDescription($blocks),
            'canonical' => route('page.show', $page->slug),
            'og_image' => $page->hero_cover->imageUrl(),
        ];

        $otherPages = StaticPage::active()
            ->ordered()
            ->where('id', '!=', $page->id)
            ->get(['id', 'title', 'slug']);

        return view('pages.show', compact('page', 'blocks', 'seo', 'otherPages'));
    }
}
