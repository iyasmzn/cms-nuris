@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Faq> $faqs */
    $faqs = $faqs ?? \App\Models\Faq::published()->get();

    $faqCategories = $faqs->pluck('category')->filter()->unique()->values();

    // Schema.org FAQPage payload — built here so Blade never sees the `@context`
    // / `@type` keys as directives.
    $faqSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqs->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq->question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => trim(strip_tags($faq->answer)),
            ],
        ])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp

@if($faqs->isNotEmpty())
<section id="faq" class="py-20 sm:py-28" style="background:var(--bg-alt, var(--bg))">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @php
            $eyebrow  = setting('section_faq_eyebrow', 'Pertanyaan Umum');
            $subtitle = setting('section_faq_subtitle', 'Jawaban atas pertanyaan yang paling sering diajukan calon santri dan orang tua.');
        @endphp
        <div class="text-center mb-12" data-aos="fade-up">
            @if($eyebrow)
                <div class="fi-label mb-3">{{ $eyebrow }}</div>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ setting('section_faq_title') ?: 'Ada yang Ingin Ditanyakan?' }}
            </h2>
            @if($subtitle)
                <p class="mt-3 text-base max-w-lg mx-auto leading-relaxed" style="color:var(--muted)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        <div x-data="{ open: null, category: 'all' }">

            {{-- Category filter — only useful when FAQs are grouped --}}
            @if($faqCategories->count() > 1)
                <div class="flex flex-wrap justify-center gap-2 mb-8" data-aos="fade-up">
                    <button type="button"
                            @click="category = 'all'; open = null"
                            :class="category === 'all' ? 'faq-chip faq-chip-active' : 'faq-chip'"
                            class="faq-chip">Semua</button>
                    @foreach($faqCategories as $faqCategory)
                        <button type="button"
                                @click="category = @js($faqCategory); open = null"
                                :class="category === @js($faqCategory) ? 'faq-chip faq-chip-active' : 'faq-chip'"
                                class="faq-chip">{{ $faqCategory }}</button>
                    @endforeach
                </div>
            @endif

            <div class="space-y-3">
                @foreach($faqs as $index => $faq)
                    <div class="fi-card overflow-hidden"
                         @if($faq->category) x-show="category === 'all' || category === @js($faq->category)" @endif
                         data-aos="fade-up" data-aos-delay="{{ min($index, 5) * 60 }}">

                        <h3>
                            <button type="button"
                                    @click="open = (open === {{ $index }} ? null : {{ $index }})"
                                    :aria-expanded="open === {{ $index }} ? 'true' : 'false'"
                                    aria-controls="faq-answer-{{ $index }}"
                                    class="w-full flex items-start justify-between gap-4 text-left px-6 py-5 transition-colors">
                                <span class="font-bold text-base sm:text-lg leading-snug" style="color:var(--text)">
                                    {{ $faq->question }}
                                </span>
                                <svg class="w-5 h-5 shrink-0 mt-0.5 transition-transform duration-300"
                                     :class="open === {{ $index }} ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                                     style="color:var(--primary)">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </h3>

                        {{-- Grid-rows trick animates the height without Alpine's collapse plugin --}}
                        <div id="faq-answer-{{ $index }}"
                             class="faq-collapse"
                             :class="open === {{ $index }} ? 'faq-collapse-open' : ''"
                             :aria-hidden="open === {{ $index }} ? 'false' : 'true'">
                            <div style="overflow:hidden">
                                <div class="faq-answer px-6 pb-6 -mt-1 text-base leading-relaxed"
                                     style="color:var(--muted)">
                                    {!! $faq->answer !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .faq-chip {
            padding: .4rem 1rem;
            border-radius: 9999px;
            font-size: .8125rem;
            font-weight: 600;
            border: 1px solid var(--border);
            background: var(--card, #fff);
            color: var(--muted);
            transition: all .2s ease;
            cursor: pointer;
        }
        .faq-chip:hover { border-color: var(--primary-300); color: var(--primary-800); }
        .faq-chip-active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .faq-collapse {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows .3s ease;
        }
        .faq-collapse-open { grid-template-rows: 1fr; }

        .faq-answer > *:first-child { margin-top: 0; }
        .faq-answer > *:last-child  { margin-bottom: 0; }
        .faq-answer p  { margin: .75rem 0; }
        .faq-answer ul,
        .faq-answer ol { margin: .75rem 0; padding-left: 1.35rem; }
        .faq-answer ul { list-style: disc; }
        .faq-answer ol { list-style: decimal; }
        .faq-answer li { margin: .3rem 0; }
        .faq-answer a  { color: var(--primary); font-weight: 600; text-decoration: underline; }
        .faq-answer strong { color: var(--text); }
    </style>

    {{-- Structured data so the FAQ can surface as a rich result in search --}}
    <script type="application/ld+json">{!! $faqSchema !!}</script>
</section>
@endif
