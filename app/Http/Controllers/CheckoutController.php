<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Hydrate cart items from the session, resolving book models from the DB.
     *
     * @return Collection<int, array{book: Book, qty: int}>
     */
    private function hydrateCart(): Collection
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return collect();
        }

        $books = Book::whereIn('id', array_keys($cart))->get()->keyBy('id');

        return collect($cart)
            ->map(fn ($item, $bookId) => [
                'book' => $books->get($bookId),
                'qty' => $item['qty'],
            ])
            ->filter(fn ($item) => $item['book'] !== null);
    }

    public function index(): View|RedirectResponse
    {
        $items = $this->hydrateCart();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('success', 'Keranjang Anda masih kosong.');
        }

        $total = $items->sum(fn ($item) => $item['book']->price * $item['qty']);

        return view('checkout.index', compact('items', 'total'));
    }

    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $items = $this->hydrateCart();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $whatsapp = preg_replace('/\D/', '', setting('shop_whatsapp', setting('social_whatsapp', '')));

        if (empty($whatsapp)) {
            return redirect()->route('checkout.index')
                ->with('error', 'Nomor WhatsApp toko belum dikonfigurasi. Hubungi admin.');
        }

        // Build the WhatsApp message
        $lines = [];
        $lines[] = '🛍️ *PESANAN BUKU BARU*';
        $lines[] = '─────────────────────';
        $lines[] = '👤 Nama    : '.$request->name;
        $lines[] = '📱 HP/WA   : '.$request->phone;
        $lines[] = '📍 Alamat  : '.$request->address;

        if ($request->notes) {
            $lines[] = '📝 Catatan : '.$request->notes;
        }

        $lines[] = '';
        $lines[] = '📚 *DAFTAR BUKU:*';

        $total = 0;
        foreach ($items as $item) {
            /** @var Book $book */
            $book = $item['book'];
            $subtotal = $book->price * $item['qty'];
            $total += $subtotal;
            $lines[] = "• {$book->title}";
            $lines[] = "  {$item['qty']} pcs × {$book->formatted_price} = Rp ".number_format($subtotal, 0, ',', '.');
        }

        $lines[] = '─────────────────────';
        $lines[] = '💰 *TOTAL: Rp '.number_format($total, 0, ',', '.').'*';
        $lines[] = '';
        $lines[] = 'Mohon konfirmasi ketersediaan dan info pengiriman. Terima kasih 🙏';

        $message = implode("\n", $lines);
        $waUrl = 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message);

        // Clear cart after checkout
        session()->forget('cart');

        return redirect($waUrl);
    }
}
