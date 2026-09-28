{{-- Seksi "FAQ": pertanyaan dari menu FAQ, opsional satu kategori saja. --}}
@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Faq> $faqs */
    $faqs = $block['data'];
    $faqId = 'ppdb-faq-'.(\Illuminate\Support\Str::slug((string) ($block['anchor'] ?? '')) ?: 'list');

    // Schema.org FAQPage — dirakit di PHP agar Blade tidak membaca `@context` sebagai direktif.
    $faqSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqs->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq->question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($faq->answer))],
        ])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp

@push('structured-data')
<script type="application/ld+json">{!! $faqSchema !!}</script>
@endpush

<div class="pl-narrow space-y-3 {{ ($block['heading_align'] ?? 'left') === 'center' ? 'mx-auto' : '' }}" x-data="{ open: 0 }">
    @foreach($faqs as $index => $faq)
        <div class="fi-card overflow-hidden" data-aos="fade-up" data-aos-delay="{{ min($index, 5) * 60 }}">
            <h3>
                <button type="button"
                        @click="open = (open === {{ $index }} ? null : {{ $index }})"
                        :aria-expanded="open === {{ $index }} ? 'true' : 'false'"
                        aria-controls="{{ $faqId }}-{{ $index }}"
                        class="w-full flex items-start justify-between gap-4 text-left px-6 py-5">
                    <span class="font-bold text-base leading-snug" style="color:var(--text)">{{ $faq->question }}</span>
                    <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-600 transition-transform duration-300"
                         :class="open === {{ $index }} ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </h3>
            <div id="{{ $faqId }}-{{ $index }}"
                 class="pl-collapse"
                 :class="open === {{ $index }} ? 'pl-collapse-open' : ''">
                <div style="overflow:hidden">
                    <div class="pl-answer px-6 pb-6 -mt-1 text-sm sm:text-base leading-relaxed" style="color:var(--muted)">
                        {!! $faq->answer !!}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
