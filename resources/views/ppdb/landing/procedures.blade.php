{{-- Seksi "Alur Pendaftaran": langkah bernomor dari Pengaturan PPDB atau jenjang terpilih. --}}
<div class="pl-grid">
    @foreach($block['data'] as $index => $step)
        <div class="fi-card p-6 pl-step" data-aos="fade-up" data-aos-delay="{{ min($index, 5) * 70 }}">
            <div class="pl-step-num">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
            @if($url = icon_url($step['icon_image'] ?? null))
                <img src="{{ $url }}" alt="{{ $step['title'] ?? '' }}" loading="lazy" class="w-9 h-9 mb-3 object-contain">
            @elseif(filled($step['icon'] ?? null))
                <div class="text-3xl mb-3">{{ $step['icon'] }}</div>
            @endif
            <h3 class="font-bold text-base mb-2" style="color:var(--text)">{{ $step['title'] ?? '' }}</h3>
            @if(filled($step['description'] ?? null))
                <p class="text-sm leading-relaxed" style="color:var(--muted)">{{ $step['description'] }}</p>
            @endif
        </div>
    @endforeach
</div>
