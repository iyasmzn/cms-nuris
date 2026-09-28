{{--
    Blok "Timeline": linimasa isian manual, khusus halaman PPDB. Tampilannya
    vertikal, zig-zag, atau horizontal (lihat PpdbLanding::TIMELINE_LAYOUTS);
    di layar ponsel ketiganya jatuh ke vertikal. Penanda tiap titik memakai
    emoji-nya, atau nomor urut bila emoji dikosongkan.
--}}
@php
    $layout = \App\Support\PpdbLanding::timelineLayout($block);
    $centered = ($block['heading_align'] ?? 'left') === 'center';
    $fill = fn (?string $text): string => trim(\App\Support\PpdbLanding::fill($text));
@endphp

<ol class="pl-tl pl-tl-{{ $layout }} {{ $centered && $layout !== 'horizontal' ? 'mx-auto' : '' }}">
    @foreach($block['timeline_items'] as $index => $item)
        @php
            $icon = trim((string) ($item['icon'] ?? ''));
            $label = $fill($item['label'] ?? '');
            $description = $fill($item['description'] ?? '');
            $highlighted = (bool) ($item['highlighted'] ?? false);
        @endphp
        <li class="pl-tl-item {{ $highlighted ? 'pl-tl-item-active' : '' }}"
            data-aos="fade-up" data-aos-delay="{{ min($index, 5) * 70 }}">
            <div class="pl-tl-marker" @if($highlighted) aria-label="Tahap disorot" @endif>
                @if($icon !== '')
                    <span class="pl-tl-marker-icon">{{ $icon }}</span>
                @else
                    {{ $index + 1 }}
                @endif
            </div>
            <div class="pl-tl-card fi-card">
                @if($label !== '')
                    <div class="pl-tl-label">{{ $label }}</div>
                @endif
                <h3 class="pl-tl-title" style="color:var(--text)">{{ $fill($item['title']) }}</h3>
                @if($description !== '')
                    <p class="pl-tl-desc" style="color:var(--muted)">{{ $description }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
