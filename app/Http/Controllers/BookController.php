<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $category = (string) $request->query('category', '');
        $sort = (string) $request->query('sort', 'default');
        $minPrice = $request->query('min_price') !== null ? (int) $request->query('min_price') : null;
        $maxPrice = $request->query('max_price') !== null ? (int) $request->query('max_price') : null;
        $inStock = $request->boolean('in_stock');

        $books = Book::available()
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('author', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            }))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($minPrice !== null, fn ($query) => $query->where('price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($query) => $query->where('price', '<=', $maxPrice))
            ->when($inStock, fn ($query) => $query->where('stock', '>', 0))
            ->when($sort === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->when($sort === 'name_asc', fn ($query) => $query->orderBy('title'))
            ->when($sort === 'newest', fn ($query) => $query->latest())
            ->when($sort === 'default', fn ($query) => $query->orderBy('sort_order')->orderBy('title'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $categories = Book::available()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // Price bounds for the range slider
        $priceStats = Book::available()->selectRaw('MIN(price) as min_p, MAX(price) as max_p')->first();
        $priceMin = (int) ($priceStats->min_p ?? 0);
        $priceMax = (int) ($priceStats->max_p ?? 200000);

        // Active filter count (for badge on mobile button)
        $activeFilters = collect([
            $q !== '',
            $category !== '',
            $minPrice !== null || $maxPrice !== null,
            $inStock,
        ])->filter()->count();

        $seo = [
            'title' => 'Toko Buku | '.config('app.name'),
            'description' => 'Beli buku pilihan dari '.config('app.name').'. Koleksi kitab, buku agama, dan referensi pendidikan berkualitas.',
            'canonical' => route('books.index'),
        ];

        return view('books.index', compact(
            'books', 'categories', 'category',
            'q', 'sort', 'minPrice', 'maxPrice', 'inStock',
            'priceMin', 'priceMax', 'activeFilters', 'seo',
        ));
    }

    public function show(Book $book): View
    {
        abort_unless($book->is_available, 404);

        $related = Book::available()
            ->where('id', '!=', $book->id)
            ->where('category', $book->category)
            ->ordered()
            ->limit(4)
            ->get();

        $seo = [
            'title' => "{$book->title} | ".config('app.name'),
            'description' => $book->description
                ? Str::limit(strip_tags($book->description), 155)
                : "Beli buku {$book->title} di toko buku ".config('app.name'),
            'canonical' => route('books.show', $book),
            'og_image' => $book->cover_url,
        ];

        return view('books.show', compact('book', 'related', 'seo'));
    }
}
