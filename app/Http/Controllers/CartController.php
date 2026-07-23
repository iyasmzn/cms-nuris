<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Return the current cart from session.
     *
     * @return array<int, array{book_id: int, qty: int}>
     */
    private function getCart(): array
    {
        return session('cart', []);
    }

    /**
     * Save cart back to session.
     *
     * @param  array<int, array{book_id: int, qty: int}>  $cart
     */
    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
    }

    /** Total number of distinct items in the cart. */
    public static function itemCount(): int
    {
        return count(session('cart', []));
    }

    public function index(): View
    {
        $cart = $this->getCart();

        $bookIds = array_keys($cart);
        $books = Book::whereIn('id', $bookIds)->get()->keyBy('id');

        $items = collect($cart)->map(fn ($item, $bookId) => [
            'book' => $books->get($bookId),
            'qty' => $item['qty'],
        ])->filter(fn ($item) => $item['book'] !== null);

        $total = $items->sum(fn ($item) => $item['book']->price * $item['qty']);

        return view('cart.index', compact('items', 'total'));
    }

    public function add(Request $request, Book $book): RedirectResponse|JsonResponse
    {
        abort_unless($book->is_available && $book->stock > 0, 422, 'Buku tidak tersedia.');

        $qty = max(1, (int) $request->input('qty', 1));

        $cart = $this->getCart();

        if (isset($cart[$book->id])) {
            $newQty = $cart[$book->id]['qty'] + $qty;
            $cart[$book->id]['qty'] = min($newQty, $book->stock);
        } else {
            $cart[$book->id] = ['book_id' => $book->id, 'qty' => min($qty, $book->stock)];
        }

        $this->saveCart($cart);

        return back()->with('success', "«{$book->title}» ditambahkan ke keranjang.");
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $qty = max(1, (int) $request->input('qty', 1));

        $cart = $this->getCart();

        if (isset($cart[$book->id])) {
            $cart[$book->id]['qty'] = min($qty, $book->stock);
            $this->saveCart($cart);
        }

        return redirect()->route('cart.index');
    }

    public function remove(Book $book): RedirectResponse
    {
        $cart = $this->getCart();
        unset($cart[$book->id]);
        $this->saveCart($cart);

        return redirect()->route('cart.index')
            ->with('success', "«{$book->title}» dihapus dari keranjang.");
    }

    public function clear(): RedirectResponse
    {
        session()->forget('cart');

        return redirect()->route('cart.index');
    }
}
