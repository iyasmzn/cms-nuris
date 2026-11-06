{{--
    Kartu capaian alumni — bagian atas seksi Jejak Alumni, dipakai halaman depan
    (sections/alumni) dan blok data di halaman & program. Stylesheet-nya di
    sections/bodies/alumni-styles, disertakan sekali oleh pemanggil.

    Params: $alumniStats — koleksi AlumniStat
--}}
{{-- ── Kartu capaian alumni ────────────────────────────────
     Gaya sengaja berbeda dari seksi Statistik: kartu mendatar
     dengan pita aksen di sisi kiri dan ikon berbingkai. --}}
@if($alumniStats->isNotEmpty())
    <div class="grid gap-4 sm:gap-5 sm:grid-cols-2 lg:grid-cols-{{ min(max($alumniStats->count(), 1), 4) }}">
        @foreach($alumniStats as $alumniStat)
            <div class="alumni-stat" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                <span class="alumni-stat-bar" aria-hidden="true"></span>

                <div class="alumni-stat-icon">
                    @if($url = icon_url($alumniStat->icon_image))
                        <img src="{{ $url }}" alt="{{ $alumniStat->label }}" loading="lazy"
                             class="w-8 h-8 object-contain">
                    @else
                        <span class="text-2xl leading-none">{{ $alumniStat->icon }}</span>
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="alumni-stat-value"><x-count-up :value="$alumniStat->value" /></div>
                    <div class="alumni-stat-label">{{ $alumniStat->label }}</div>
                    @if($alumniStat->sub)
                        <div class="alumni-stat-sub">{{ $alumniStat->sub }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
