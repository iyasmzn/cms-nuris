{{-- Tombol kembali dan tautan berbagi di bawah isi halaman statis. --}}
<div class="flex items-center justify-between">
    <a href="{{ url()->previous() === url()->current() ? route('home') : url()->previous() }}"
       class="btn-outline group text-sm">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
        </svg>
        Kembali
    </a>

    {{-- Share --}}
    <div class="flex items-center gap-1.5">
        <span class="text-xs text-gray-400 mr-1">Bagikan:</span>
        <a href="https://wa.me/?text={{ urlencode($page->title.' — '.route('page.show', $page->slug)) }}"
           target="_blank" rel="noopener"
           class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold transition-opacity hover:opacity-80"
           style="background:#25d366" title="Bagikan via WhatsApp">
            WA
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('page.show', $page->slug)) }}"
           target="_blank" rel="noopener"
           class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold transition-opacity hover:opacity-80"
           style="background:#1877f2" title="Bagikan via Facebook">
            FB
        </a>
        <button onclick="navigator.clipboard.writeText('{{ route('page.show', $page->slug) }}').then(()=>{ this.textContent='✓'; setTimeout(()=>{ this.textContent='🔗'; },2000); })"
                class="w-8 h-8 rounded-lg border flex items-center justify-center text-sm transition-colors"
                style="border-color:var(--border)" onmouseover="this.style.background='var(--primary-50)';this.style.borderColor='var(--primary-300)'" onmouseout="this.style.background='';this.style.borderColor='var(--border)'" title="Salin link">
            🔗
        </button>
    </div>
</div>
