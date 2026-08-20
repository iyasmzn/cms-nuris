@props([
    'overHero' => true,
])

@php
    /**
     * Shared public navbar.
     *
     * Reads menu items from the `nav_items` setting so the landing page and every
     * inner page render an identical menu. Pass `:over-hero="true"` on pages with a
     * full-bleed dark hero behind the navbar (e.g. the landing page): the bar starts
     * transparent with light text and turns solid on scroll. Otherwise it stays solid.
     */
    $navItems = collect(json_decode(setting('nav_items', ''), true) ?: [
        ['label' => 'Beranda',  'url' => '/',         'target' => '_self', 'is_active' => true, 'children' => []],
        ['label' => 'Profil',   'url' => '#profil',   'target' => '_self', 'is_active' => true, 'children' => []],
        ['label' => 'SPMB',     'url' => '#spmb',     'target' => '_self', 'is_active' => true, 'children' => []],
        ['label' => 'Akademik', 'url' => '#akademik', 'target' => '_self', 'is_active' => true, 'children' => []],
        ['label' => 'Guru',     'url' => '/guru',     'target' => '_self', 'is_active' => true, 'children' => []],
        ['label' => 'Blog',     'url' => '/blog',     'target' => '_self', 'is_active' => true, 'children' => []],
        ['label' => 'Kontak',   'url' => '#kontak',   'target' => '_self', 'is_active' => true, 'children' => []],
    ])->where('is_active', true)->values();

    /**
     * Normalise a menu URL and resolve the scroll-spy key used to highlight it.
     * Hash-only links (e.g. `#spmb`) are prefixed with `/` so they jump to the home
     * page section from any page.
     *
     * The `spy` key drives highlighting in Alpine:
     * - `null`  the link points at another page, so it is never highlighted here;
     * - `''`    the link points at the current page itself (highlight while no
     *           tracked section is in view);
     * - `'id'`  the link points at a section of the current page (highlight while
     *           that section is the one in view).
     *
     * @return array{url: string, spy: ?string}
     */
    $resolveNav = function (string $url): array {
        $normalized = str_starts_with($url, '#') ? '/' . $url : $url;
        $path = parse_url($normalized, PHP_URL_PATH) ?: '/';
        $fragment = parse_url($normalized, PHP_URL_FRAGMENT);
        $host = parse_url($normalized, PHP_URL_HOST);

        $onCurrentPage = ($host === null || $host === request()->getHost())
            && ($path === '/'
                ? request()->is('/')
                : request()->is(ltrim($path, '/'), ltrim($path, '/') . '/*'));

        // A bare `#` names no destination — it is a placeholder for an entry that
        // only exists to open its dropdown. Left alone it resolves to "the current
        // page, no section", so on the landing page every such entry would claim
        // the you-are-here highlight at the same time as Beranda.
        $isPlaceholder = in_array(trim($url), ['', '#', '/#'], true);

        return [
            'url' => $normalized,
            'spy' => ($onCurrentPage && ! $isPlaceholder) ? ($fragment ?? '') : null,
        ];
    };

    /**
     * The spy keys a dropdown highlights on: its children, plus its own link when
     * that link actually names somewhere. A parent pointing at the home page as a
     * whole is just a menu opener — Beranda already owns that highlight.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $children
     * @return array<int, ?string>
     */
    $branchSpies = function (array $item, $children) use ($resolveNav): array {
        $parent = $resolveNav($item['url'] ?? '/');
        $isMenuOpener = $parent['spy'] === '' && (parse_url($parent['url'], PHP_URL_PATH) ?: '/') === '/';

        return $children
            ->pluck('url')
            ->map(fn (string $url): ?string => $resolveNav($url)['spy'])
            ->when(! $isMenuOpener, fn ($spies) => $spies->prepend($parent['spy']))
            ->all();
    };

    /** Section ids on the current page that the menu links to — the scroll-spy targets. */
    $navSpyIds = $navItems
        ->flatMap(fn (array $item) => array_merge([$item], collect($item['children'] ?? [])->where('is_active', true)->all()))
        ->map(fn (array $entry) => $resolveNav($entry['url'] ?? '/')['spy'])
        ->filter(fn (?string $spy) => filled($spy))
        ->unique()
        ->values()
        ->all();
@endphp

@once
    <style>
        [x-cloak] { display: none !important; }
        /* Center menu items when they fit, but fall back to top-aligned + scrollable
           when taller than the viewport so the first item is never clipped/unreachable. */
        .mobile-nav-scroll { justify-content: safe center; }

        /* ── Desktop menu links ─────────────────────────────────
           `.is-active` is toggled from Alpine: it marks the link for the page the
           visitor is on, or — for hash links — the section currently in view. Hover
           and active share the same treatment so the highlight reads as one idea. */
        .nav-link {
            position: relative;
            display: inline-flex; align-items: center; gap: .25rem;
            padding: .5rem .75rem;
            border-radius: .5rem;
            font-size: .875rem; font-weight: 500;
            transition: color .2s, background .2s, font-weight .2s;
        }
        .nav-link::after {
            content: '';
            position: absolute; left: 50%; right: 50%; bottom: .25rem;
            height: 2px; border-radius: 2px;
            background: currentColor; opacity: 0;
            transition: left .25s ease, right .25s ease, opacity .2s ease;
        }
        .nav-link:hover::after,
        .nav-link.is-active::after { left: .75rem; right: .75rem; opacity: 1; }
        .nav-link.is-active { font-weight: 600; }

        .nav-link-solid { color: #6b7280; }
        .nav-link-solid:hover,
        .nav-link-solid.is-active { background: color-mix(in oklab, var(--primary) 8%, white); color: var(--primary); }

        .nav-link-over { color: rgba(255,255,255,.8); }
        .nav-link-over:hover,
        .nav-link-over.is-active { background: rgba(255,255,255,.14); color: #fff; }

        .nav-dropdown-item:hover { background: color-mix(in oklab, var(--primary) 8%, white); color: var(--primary); }
        .nav-dropdown-item.is-active { background: color-mix(in oklab, var(--primary) 6%, white); color: var(--primary); font-weight: 600; }
        .nav-dropdown-icon { width: 1.25rem; text-align: center; font-size: 1rem; line-height: 1.25rem; }
        .nav-dropdown-desc { color: #9ca3af; font-weight: 400; }
        .nav-dropdown-item:hover .nav-dropdown-desc,
        .nav-dropdown-item.is-active .nav-dropdown-desc { color: color-mix(in oklab, var(--primary) 45%, #6b7280); }

        /* Keyboard users need to see where they are; a mouse hover shows it by itself. */
        .nav-link:focus-visible,
        .nav-dropdown-item:focus-visible { outline: 2px solid var(--primary); outline-offset: -2px; }
        .nav-icon-scrolled:hover { background: color-mix(in oklab, var(--primary) 8%, white); }
        .nav-auth-scrolled:hover { border-color: var(--primary); color: var(--primary); }

        /* ── Mobile menu links ──────────────────────────────────── */
        .mobile-nav-item:hover .mobile-nav-num,
        .mobile-nav-item.is-active .mobile-nav-num { color: var(--primary); }
        .mobile-nav-item:hover .mobile-nav-label,
        .mobile-nav-item.is-active .mobile-nav-label { color: #fff; }
        .mobile-nav-item:hover,
        .mobile-nav-item.is-active { border-color: color-mix(in oklab, var(--primary) 45%, transparent); }
        .mobile-nav-item.is-active { padding-left: .5rem; }
        .mobile-nav-arrow:hover { color: var(--primary); }
        .mobile-subnav-hover:hover,
        .mobile-subnav-hover.is-active { color: color-mix(in oklab, var(--primary) 60%, white); }
        .mobile-subnav-hover.is-active { font-weight: 600; }
    </style>
@endonce

{{-- display:contents so the wrapper generates no box — the sticky <header> and
     fixed overlay behave as direct children of <body> (sticky would otherwise break). --}}
<div style="display:contents"
     x-data="{
        scrolled: false,
        mobileOpen: false,
        over: {{ $overHero ? 'true' : 'false' }},
        activeHash: '',
        spyIds: @js($navSpyIds),
        spyTargets: [],
        get solid() { return ! this.over || this.scrolled; },

        /** True when a menu entry points at the page — or section — currently in view. */
        navActive(spy) {
            if (spy === null) { return false; }
            if (spy === '') { return this.activeHash === ''; }
            return this.activeHash === spy;
        },
        navActiveAny(spies) { return spies.some(spy => this.navActive(spy)); },
        navLinkClass(active) {
            return (this.solid ? 'nav-link-solid' : 'nav-link-over') + (active ? ' is-active' : '');
        },

        /** Collect the linked sections that actually exist, in document order. */
        refreshSpyTargets() {
            this.spyTargets = this.spyIds
                .map(id => document.getElementById(id))
                .filter(Boolean)
                .sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top);
        },
        /** Highlight the last section whose top has passed under the navbar. */
        syncSpy() {
            if (! this.spyTargets.length) { return; }
            let current = '';
            for (const target of this.spyTargets) {
                if (target.getBoundingClientRect().top <= 96) { current = target.id; }
            }
            if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
                current = this.spyTargets[this.spyTargets.length - 1].id;
            }
            this.activeHash = current;
        },

        init() {
            let ticking = false;
            const onScroll = () => {
                if (ticking) { return; }
                ticking = true;
                requestAnimationFrame(() => {
                    this.scrolled = window.scrollY > 60;
                    this.syncSpy();
                    ticking = false;
                });
            };
            const onResize = () => { this.refreshSpyTargets(); this.syncSpy(); };

            this.scrolled = window.scrollY > 60;
            this.$nextTick(onResize);
            window.addEventListener('load', onResize);
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onResize, { passive: true });
        },
     }">

    {{-- ── Navbar ─────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 transition-all duration-300"
            :style="solid
                ? 'background:rgba(255,255,255,0.92);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,.08);box-shadow:0 1px 12px rgba(0,0,0,.05)'
                : 'background:transparent;border-bottom:1px solid transparent'">

        {{-- Amber bar — only while solid --}}
        <div class="amber-bar overflow-hidden transition-all duration-300"
             :style="solid ? 'height:3px;opacity:1' : 'height:0;opacity:0'"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">

                {{-- Logo --}}
                <a href="/" class="flex items-center gap-2.5 shrink-0 min-w-0">
                    @if(setting('site_logo'))
                        <img src="{{ asset('storage/' . setting('site_logo')) }}"
                             alt="{{ setting('site_name', config('app.name')) }}"
                             class="w-9 h-9 rounded-xl object-contain shrink-0">
                    @else
                        <div class="w-9 h-9 shrink-0 rounded-xl shadow flex items-center justify-center" style="background:var(--primary)">
                            <span class="text-white font-extrabold text-base">{{ strtoupper(substr(setting('site_name', config('app.name', 'S')), 0, 1)) }}</span>
                        </div>
                    @endif
                    <div class="leading-tight min-w-0">
                        <div class="font-bold text-sm truncate transition-colors duration-300"
                             :class="solid ? 'text-gray-900' : 'text-white'">
                            {{ setting('site_name', config('app.name', "Qurrota A'yun")) }}
                        </div>
                        <div class="text-[10px] font-medium uppercase tracking-widest transition-colors duration-300"
                             :style="solid ? 'color:var(--primary)' : 'color:color-mix(in oklab,var(--primary) 65%,white)'">
                            {{ setting('site_tagline', 'Unggul · Berkarakter') }}
                        </div>
                    </div>
                </a>

                {{-- Right: nav links + cart + auth + hamburger --}}
                <div class="flex items-center gap-2">

                    {{-- Desktop nav --}}
                    <nav class="hidden lg:flex items-center gap-0.5">
                        @foreach($navItems as $item)
                            @php $children = collect($item['children'] ?? [])->where('is_active', true)->values(); @endphp
                            @if($children->isNotEmpty())
                                {{-- A dropdown highlights when its own link, or any of its children, targets what is in view. --}}
                                @php
                                    $spies = $branchSpies($item, $children);
                                    /** Icons or descriptions need a roomier panel than a bare list of labels. */
                                    $isRichPanel = $children->contains(fn (array $child): bool => filled($child['icon'] ?? null) || filled($child['description'] ?? null));
                                @endphp
                                <div x-data="{
                                        dropOpen: false,
                                        spies: @js($spies),
                                        closeTimer: null,
                                        /* Touch devices open on tap: hover fires there too, and would fight the click. */
                                        canHover: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
                                        open() { clearTimeout(this.closeTimer); this.dropOpen = true },
                                        close() { clearTimeout(this.closeTimer); this.dropOpen = false },
                                        /* A grace period so the cursor can cross into the panel without it closing. */
                                        closeSoon() { clearTimeout(this.closeTimer); this.closeTimer = setTimeout(() => this.dropOpen = false, 150) },
                                        toggle() { (this.canHover || ! this.dropOpen) ? this.open() : this.close() },
                                        links() { return [...this.$refs.panel.querySelectorAll('a')] },
                                        focusItem(index) {
                                            this.$nextTick(() => {
                                                const links = this.links();
                                                if (links.length) { links[(index + links.length) % links.length].focus() }
                                            });
                                        },
                                        moveFocus(step) { this.focusItem(this.links().indexOf(document.activeElement) + step) },
                                        dismiss() { this.close(); this.$refs.trigger.focus() },
                                     }"
                                     @mouseenter="canHover && open()"
                                     @mouseleave="canHover && closeSoon()"
                                     @focusout="$el.contains($event.relatedTarget) || close()"
                                     @keydown.escape.prevent="dismiss()"
                                     class="relative">
                                    <button type="button"
                                            x-ref="trigger"
                                            @click="toggle()"
                                            @keydown.down.prevent="open(); focusItem(0)"
                                            @keydown.up.prevent="open(); focusItem(-1)"
                                            aria-haspopup="true"
                                            aria-controls="nav-drop-{{ $loop->index }}"
                                            :aria-expanded="dropOpen"
                                            class="nav-link"
                                            :class="navLinkClass(navActiveAny(spies))">
                                        {{ $item['label'] }}
                                        <svg class="w-3 h-3 transition-transform duration-200" :class="dropOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    {{-- The panel sits under a padded wrapper rather than a margin: a real gap
                                         between button and panel drops the hover on the way down. --}}
                                    <div x-show="dropOpen"
                                         x-cloak
                                         id="nav-drop-{{ $loop->index }}"
                                         x-ref="panel"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100 translate-y-0"
                                         x-transition:leave-end="opacity-0 -translate-y-1"
                                         @keydown.down.prevent="moveFocus(1)"
                                         @keydown.up.prevent="moveFocus(-1)"
                                         @keydown.home.prevent="focusItem(0)"
                                         @keydown.end.prevent="focusItem(-1)"
                                         class="absolute top-full left-0 pt-1.5 z-50">
                                        <div class="{{ $isRichPanel ? 'min-w-64' : 'min-w-48' }} bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden py-1">
                                            @foreach($children as $child)
                                                @php $childNav = $resolveNav($child['url']); @endphp
                                                <a href="{{ $childNav['url'] }}" target="{{ $child['target'] ?? '_self' }}"
                                                   x-data="{ spy: @js($childNav['spy']) }"
                                                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 nav-dropdown-item transition-colors"
                                                   :class="navActive(spy) ? 'is-active' : ''"
                                                   :aria-current="navActive(spy) ? 'page' : null">
                                                    @if(filled($child['icon'] ?? null))
                                                        <span class="nav-dropdown-icon shrink-0" aria-hidden="true">{{ $child['icon'] }}</span>
                                                    @else
                                                        <span class="w-1 h-1 rounded-full shrink-0" style="background:var(--primary)"></span>
                                                    @endif
                                                    <span class="min-w-0">
                                                        <span class="block leading-snug">{{ $child['label'] }}</span>
                                                        @if(filled($child['description'] ?? null))
                                                            <span class="nav-dropdown-desc block text-xs leading-snug mt-0.5">{{ $child['description'] }}</span>
                                                        @endif
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                @php $nav = $resolveNav($item['url']); @endphp
                                <a href="{{ $nav['url'] }}" target="{{ $item['target'] ?? '_self' }}"
                                   x-data="{ spy: @js($nav['spy']) }"
                                   class="nav-link"
                                   :class="navLinkClass(navActive(spy))"
                                   :aria-current="navActive(spy) ? 'page' : null">
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </nav>

                    {{-- Auth (desktop) --}}
                    @auth
                        <div x-data="{ userDropOpen: false }" class="relative hidden sm:block">
                            <button @click="userDropOpen = !userDropOpen"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold border transition-all duration-200"
                                    :class="solid
                                        ? 'border-gray-200 text-gray-700 bg-white nav-auth-scrolled'
                                        : 'border-white/40 text-white hover:bg-white/10'">
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}"
                                     class="w-6 h-6 rounded-full object-cover shrink-0">
                                <span class="hidden md:inline max-w-24 truncate">{{ Str::limit(Auth::user()->name, 12) }}</span>
                                <svg class="w-3 h-3 transition-transform" :class="userDropOpen ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="userDropOpen" @click.outside="userDropOpen = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                 class="absolute right-0 top-full mt-1.5 w-52 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50 py-1.5">

                                <div class="px-4 py-2.5 border-b border-gray-100">
                                    <p class="text-sm font-semibold truncate" style="color:var(--text)">{{ Auth::user()->name }}</p>
                                    <p class="text-xs truncate" style="color:var(--muted)">{{ Auth::user()->email }}</p>
                                </div>

                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm nav-dropdown-item transition-colors"
                                   style="color:var(--text)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Pengaturan Profil
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @elseif(feature_enabled('login_register'))
                        <a href="{{ route('login') }}"
                           class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-all duration-200"
                           :class="solid
                               ? 'border-gray-200 text-gray-700 bg-white nav-auth-scrolled'
                               : 'border-white/40 text-white hover:bg-white/10'">
                            Masuk
                        </a>
                    @endauth

                    {{-- Hamburger --}}
                    <button @click="mobileOpen = ! mobileOpen"
                            class="lg:hidden w-9 h-9 rounded-lg flex flex-col items-center justify-center gap-1.5 transition-colors"
                            :class="solid ? 'nav-icon-scrolled' : 'hover:bg-white/10'"
                            :aria-expanded="mobileOpen" aria-label="Menu navigasi">
                        <span class="w-5 h-0.5 rounded transition-all duration-200"
                              :class="[mobileOpen ? 'rotate-45 translate-y-2' : '', solid ? 'bg-gray-700' : 'bg-white']"></span>
                        <span class="w-5 h-0.5 rounded transition-all duration-200"
                              :class="[mobileOpen ? 'opacity-0' : '', solid ? 'bg-gray-700' : 'bg-white']"></span>
                        <span class="w-5 h-0.5 rounded transition-all duration-200"
                              :class="[mobileOpen ? '-rotate-45 -translate-y-2' : '', solid ? 'bg-gray-700' : 'bg-white']"></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    {{-- ── Mobile full-screen menu overlay ────────────────────── --}}
    <div x-show="mobileOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="lg:hidden fixed inset-0 flex flex-col"
         style="z-index:60; background:linear-gradient(145deg,#0f172a 0%,#1a2744 50%,#0f2236 100%)">

        {{-- Top bar: logo + close --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 shrink-0">
            <a href="/" @click="mobileOpen = false" class="flex items-center gap-3">
                @if(setting('site_logo'))
                    <img src="{{ asset('storage/' . setting('site_logo')) }}"
                         alt="{{ setting('site_name', config('app.name')) }}"
                         class="w-10 h-10 rounded-xl object-contain">
                @else
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg" style="background:var(--primary)">
                        <span class="text-white font-extrabold text-base">{{ strtoupper(substr(setting('site_name', config('app.name', 'S')), 0, 1)) }}</span>
                    </div>
                @endif
                <div>
                    <div class="font-bold text-white text-sm">{{ setting('site_name', config('app.name', "Qurrota A'yun")) }}</div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest" style="color:color-mix(in oklab,var(--primary) 65%,white)">{{ setting('site_tagline', 'Unggul · Berkarakter') }}</div>
                </div>
            </a>

            <button @click="mobileOpen = false"
                    class="w-10 h-10 rounded-xl border border-white/20 flex items-center justify-center text-white/70 hover:text-white hover:border-white/40 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Nav items --}}
        <nav class="mobile-nav-scroll flex-1 overflow-y-auto px-6 py-6 flex flex-col gap-1">
            @foreach($navItems as $i => $item)
                @php $children = collect($item['children'] ?? [])->where('is_active', true)->values(); @endphp
                @if($children->isNotEmpty())
                    @php $spies = $branchSpies($item, $children); @endphp
                    <div x-data="{ mobileSubOpen: false, spies: @js($spies) }">
                        <button @click="mobileSubOpen = ! mobileSubOpen"
                                :aria-expanded="mobileSubOpen"
                                class="mobile-nav-item w-full flex items-center gap-4 py-3.5 border-b border-white/8 transition-all duration-200"
                                :class="navActiveAny(spies) ? 'is-active' : ''">
                            <span class="mobile-nav-num text-xs font-bold text-white/25 transition-colors w-6 shrink-0">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="mobile-nav-label text-2xl font-bold text-white/70 transition-colors tracking-tight flex-1 text-left">{{ $item['label'] }}</span>
                            <svg class="mobile-nav-arrow w-4 h-4 text-white/20 shrink-0 transition-all duration-200"
                                 :class="mobileSubOpen ? 'rotate-90' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="mobileSubOpen"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="pl-10 pb-2 space-y-1">
                            @foreach($children as $child)
                                @php $childNav = $resolveNav($child['url']); @endphp
                                <a href="{{ $childNav['url'] }}" target="{{ $child['target'] ?? '_self' }}"
                                   @click="mobileOpen = false"
                                   x-data="{ spy: @js($childNav['spy']) }"
                                   class="mobile-subnav-hover flex items-center gap-2 py-2 text-lg font-medium text-white/55 transition-colors"
                                   :class="navActive(spy) ? 'is-active' : ''"
                                   :aria-current="navActive(spy) ? 'page' : null">
                                    @if(filled($child['icon'] ?? null))
                                        <span class="w-5 text-center text-base shrink-0" aria-hidden="true">{{ $child['icon'] }}</span>
                                    @else
                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    @endif
                                    <span class="min-w-0">
                                        <span class="block leading-snug">{{ $child['label'] }}</span>
                                        @if(filled($child['description'] ?? null))
                                            <span class="block text-xs font-normal text-white/35 leading-snug mt-0.5">{{ $child['description'] }}</span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php $nav = $resolveNav($item['url']); @endphp
                    <a href="{{ $nav['url'] }}" target="{{ $item['target'] ?? '_self' }}"
                       @click="mobileOpen = false"
                       x-data="{ spy: @js($nav['spy']) }"
                       class="mobile-nav-item group flex items-center gap-4 py-3.5 border-b border-white/8 transition-all duration-200"
                       :class="navActive(spy) ? 'is-active' : ''"
                       :aria-current="navActive(spy) ? 'page' : null">
                        <span class="mobile-nav-num text-xs font-bold text-white/25 transition-colors w-6 shrink-0">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="mobile-nav-label text-2xl font-bold text-white/70 transition-colors tracking-tight">{{ $item['label'] }}</span>
                        <svg class="mobile-nav-arrow w-4 h-4 text-white/20 ml-auto shrink-0 transition-all group-hover:translate-x-1 duration-200"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endif
            @endforeach
        </nav>

        {{-- Auth footer --}}
        <div class="shrink-0 px-6 py-6 border-t border-white/10 space-y-3">
            @auth
                <div class="flex items-center gap-3">
                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}"
                         class="w-10 h-10 rounded-full object-cover shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-white/50 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" @click="mobileOpen = false"
                   class="flex items-center justify-center gap-2 w-full py-3 rounded-xl border border-white/25 text-white/80 font-semibold hover:bg-white/10 hover:text-white transition-colors">
                    Pengaturan Profil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-red-500/90 text-white font-semibold hover:bg-red-500 transition-colors">
                        Keluar
                    </button>
                </form>
            @elseif(feature_enabled('login_register'))
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="btn-primary flex items-center justify-center gap-2 w-full py-3 rounded-xl">
                        Daftar SPMB Sekarang
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                @endif
                <a href="{{ route('login') }}" @click="mobileOpen = false"
                   class="flex items-center justify-center w-full py-3 rounded-xl border border-white/25 text-white/80 font-semibold hover:bg-white/10 hover:text-white transition-colors">
                    Masuk
                </a>
            @endauth

            <p class="text-center text-xs text-white/30 pt-1">© {{ date('Y') }} {{ setting('site_name', config('app.name')) }}</p>
        </div>
    </div>
</div>
