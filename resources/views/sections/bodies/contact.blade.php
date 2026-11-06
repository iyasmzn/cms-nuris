{{--
    Isi seksi Kontak — kartu kontak, peta Google Maps, dan tombol WhatsApp/email,
    dipakai halaman depan (sections/contact) dan blok data di halaman & program.

    Params:
      $contactItems — koleksi kontak dari contact_items() (tidak kosong)
      $dark         — opsional (bawaan true), gaya terang-di-atas-gelap milik halaman
                      depan; false memakai token tema sehingga terbaca di latar apa pun
      $showMap      — opsional (bawaan true), tampilkan peta bila URL embed-nya diisi
      $showButtons  — opsional (bawaan true), tampilkan tombol WhatsApp & email
--}}
@php
    $dark = $dark ?? true;
    $showMap = $showMap ?? true;
    $showButtons = $showButtons ?? true;
@endphp

{{-- Contact Cards Grid --}}
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-{{ min($contactItems->count(), 3) }} xl:grid-cols-{{ min($contactItems->count(), 4) }}">
    @foreach($contactItems as $ci)
        <div class="{{ $dark ? 'group relative overflow-hidden rounded-3xl border bg-white/5 backdrop-blur-sm p-7 transition-all duration-300 hover:bg-white/10 hover:border-amber-400/30 hover:shadow-xl hover:shadow-amber-500/10 hover:-translate-y-1' : 'fi-card fi-card-hover group relative overflow-hidden p-7' }}"
             @if($dark) style="border-color:rgba(255,255,255,.08)" @endif
             data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">

            {{-- Subtle glow on hover --}}
            <div class="absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                 style="background:radial-gradient(ellipse at top left,rgba(217,119,6,.07),transparent 70%)"></div>

            {{-- Icon --}}
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-5 transition-all duration-300 group-hover:scale-110 overflow-hidden"
                 style="{{ $dark ? 'background:rgba(217,119,6,.1);border:1px solid rgba(217,119,6,.2)' : 'background:var(--primary-50);border:1px solid var(--primary-100)' }}">
                @if($url = icon_url($ci->icon_image))
                    <img src="{{ $url }}" alt="{{ $ci->label }}" loading="lazy" class="w-8 h-8 object-contain">
                @else
                    {{ $ci->icon }}
                @endif
            </div>

            {{-- Content --}}
            <div class="fi-label mb-1.5" @if($dark) style="color:rgba(217,119,6,.75);letter-spacing:.1em" @endif>{{ $ci->label }}</div>
            <div class="text-sm font-medium leading-relaxed mb-5 {{ $dark ? 'text-white/80' : '' }}" @unless($dark) style="color:var(--text)" @endunless>{{ $ci->value }}</div>

            {{-- Link --}}
            @if($ci->link)
                <a href="{{ $ci->link }}"
                   target="{{ str_starts_with($ci->link, 'http') ? '_blank' : '_self' }}"
                   rel="{{ str_starts_with($ci->link, 'http') ? 'noopener noreferrer' : '' }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold transition-all group/link"
                   style="color:{{ $dark ? 'var(--primary-400)' : 'var(--primary)' }}">
                    Buka
                    <svg class="w-3.5 h-3.5 transition-transform group-hover/link:translate-x-0.5"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            @endif
        </div>
    @endforeach
</div>

{{-- Google Maps --}}
@if($showMap && setting('contact_map_url') && str_starts_with(setting('contact_map_url'), 'https://www.google.com/maps/embed'))
<div class="mt-14" data-aos="fade-up" data-aos-delay="150">
    <div class="relative overflow-hidden rounded-3xl border {{ $dark ? 'shadow-2xl shadow-black/40' : 'shadow-sm' }}"
         style="border-color:{{ $dark ? 'rgba(255,255,255,.08)' : 'var(--border)' }}">
        <div class="flex items-center gap-3 px-6 py-4 border-b"
             style="{{ $dark ? 'background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.08)' : 'background:var(--card);border-color:var(--border)' }}">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg shrink-0"
                 style="{{ $dark ? 'background:rgba(217,119,6,.12);border:1px solid rgba(217,119,6,.2)' : 'background:var(--primary-50);border:1px solid var(--primary-100)' }}">📍</div>
            {{-- min-w-0 supaya alamat panjang dipangkas, bukan melebarkan barisnya di layar kecil --}}
            <div class="min-w-0">
                <div class="text-sm font-semibold {{ $dark ? 'text-white/90' : '' }}" @unless($dark) style="color:var(--text)" @endunless>Lokasi Sekolah</div>
                @if(setting('contact_address'))
                    <div class="text-xs truncate {{ $dark ? 'text-white/40' : '' }}" @unless($dark) style="color:var(--muted)" @endunless>{{ setting('contact_address') }}</div>
                @endif
            </div>
            <a href="{{ str_replace('/embed?', '/search?', setting('contact_map_url')) }}"
               target="_blank" rel="noopener noreferrer"
               class="ms-auto inline-flex items-center gap-1.5 text-xs font-semibold transition-opacity hover:opacity-75"
               style="color:{{ $dark ? 'var(--primary-400)' : 'var(--primary)' }}">
                Buka di Maps
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
        <iframe
            src="{{ setting('contact_map_url') }}"
            width="100%"
            height="400"
            style="border:0;display:block;"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Lokasi {{ setting('site_name') }}"
        ></iframe>
    </div>
</div>
@endif

{{-- Bottom CTA --}}
@if($showButtons)
<div class="mt-16 text-center" data-aos="fade-up" data-aos-delay="200">
    <p class="text-sm mb-6 {{ $dark ? 'text-white/40' : '' }}" @unless($dark) style="color:var(--muted)" @endunless>Atau kunjungi langsung kantor kami pada hari dan jam operasional.</p>
    <div class="flex flex-wrap gap-4 justify-center">
        @if(setting('social_whatsapp'))
            <a href="https://wa.me/{{ setting('social_whatsapp') }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2.5 px-7 py-3.5 rounded-2xl font-semibold text-base transition-all hover:opacity-90 shadow-lg shadow-green-500/20 hover:-translate-y-0.5"
               style="background:#22c55e;color:#fff">
                💬 Chat WhatsApp
            </a>
        @endif
        @if(setting('contact_email'))
            <a href="mailto:{{ setting('contact_email') }}"
               @if($dark)
               class="inline-flex items-center gap-2.5 px-7 py-3.5 rounded-2xl border font-semibold text-base text-white transition-all hover:bg-white/10 hover:-translate-y-0.5"
               style="border-color:rgba(255,255,255,.2)"
               @else
               class="btn-outline"
               @endif>
                ✉️ Kirim Email
            </a>
        @endif
    </div>
</div>
@endif
