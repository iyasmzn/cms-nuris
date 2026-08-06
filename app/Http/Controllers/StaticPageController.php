<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = StaticPage::active()
            ->where('slug', $slug)
            ->firstOrFail();

        $seo = [
            'title' => $page->title.' | '.setting('site_name', config('app.name')),
            'description' => $page->seo_description,
            'canonical' => route('page.show', $page->slug),
            'og_image' => $page->hero_cover->imageUrl(),
        ];

        $otherPages = StaticPage::active()
            ->ordered()
            ->where('id', '!=', $page->id)
            ->get(['id', 'title', 'slug']);

        return view('pages.show', compact('page', 'seo', 'otherPages'));
    }
}
