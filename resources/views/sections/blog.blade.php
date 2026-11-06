<x-section-background :config="section_background('section_blog')"
                      background="var(--bg)"
                      id="blog" class="py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        @php
            $eyebrow  = setting('section_blog_eyebrow', 'Berita & Artikel');
            $subtitle = setting('section_blog_subtitle', 'Blog inspiratif dari berbagai sumber.');
        @endphp
        <div class="flex items-end justify-between gap-6 mb-14" data-aos="fade-up">
            <div>
                @if($eyebrow)
                    <div class="fi-label mb-3">{{ $eyebrow }}</div>
                @endif
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">{{ setting('section_blog_title') ?: 'Artikel' }}</h2>
                @if($subtitle)
                    <p class="mt-2 text-base max-w-md leading-relaxed" style="color:var(--muted)">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
            <a href="{{ route('blog.index') }}"
               class="shrink-0 inline-flex items-center gap-1.5 text-sm font-semibold transition-colors hover:opacity-75"
               style="color:var(--primary)">
                Semua Artikel
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        @if($posts->isNotEmpty())
            @include('sections.bodies.posts', ['posts' => $posts])
        @else
            <div class="text-center py-20 fi-card">
                <div class="text-5xl mb-4">📰</div>
                <p class="text-base font-medium" style="color:var(--muted)">Belum ada artikel yang dipublikasikan.</p>
            </div>
        @endif

        <div class="mt-10 text-center">
            <a href="{{ route('blog.index') }}" class="btn-outline">Lihat Semua Artikel</a>
        </div>
    </div>
</x-section-background>
