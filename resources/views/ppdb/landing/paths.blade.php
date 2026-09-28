{{--
    Seksi "Jalur Penerimaan": kartu jalur aktif dalam salah satu tampilan
    (lihat PpdbLanding::PATH_LAYOUTS):

      • image — gambar sampul lebar di atas kartu
      • icon  — ikon besar di pojok kartu, tanpa bidang gambar
      • list  — baris rapat: ikon di kiri, teks di kanan

    Jalur yang punya tautan "Lihat Selengkapnya" menjadi satu kartu yang bisa
    diklik utuh; yang tidak punya tetap kartu biasa tanpa efek hover.
--}}
@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\AdmissionPath> $paths */
    $paths = $block['data'];
    $layout = \App\Support\PpdbLanding::pathLayout($block, $paths);
    $columns = (int) ($block['paths_columns'] ?? \App\Support\PpdbLanding::DEFAULT_PATH_COLUMNS);
    $columns = array_key_exists($columns, \App\Models\ContentSection::ITEM_COLUMNS) ? $columns : \App\Support\PpdbLanding::DEFAULT_PATH_COLUMNS;
    $isList = $layout === 'list';
@endphp

<div class="pl-cols pl-cols-{{ $columns }} pl-paths-{{ $layout }}">
    @foreach($paths as $path)
        @php
            $coverUrl = icon_url($path->image);
            $iconUrl = icon_url($path->icon_image);
            $detailUrl = $path->detail_url;
            $detailIsExternal = $detailUrl && \Illuminate\Support\Str::startsWith($detailUrl, ['http://', 'https://']);
            $tag = $detailUrl ? 'a' : 'div';
        @endphp
        <{{ $tag }} @if($detailUrl) href="{{ $detailUrl }}" @if($detailIsExternal) target="_blank" rel="noopener" @endif @endif
             class="{{ $detailUrl ? 'pl-card-hover pl-card-link' : '' }} fi-card border overflow-hidden {{ $isList ? 'pl-path-row' : 'flex flex-col' }}"
             style="border-color:var(--border)"
             data-aos="fade-up" data-aos-delay="{{ min($loop->index, 5) * 70 }}">

            @if($layout === 'image')
                {{-- Sampul lebar, atau ikon besar bila jalur ini belum bergambar --}}
                <div class="pl-path-media">
                    @if($coverUrl)
                        <img src="{{ $coverUrl }}" alt="{{ $path->name }}" loading="lazy">
                    @elseif($iconUrl)
                        <img src="{{ $iconUrl }}" alt="{{ $path->name }}" loading="lazy" class="pl-path-media-icon">
                    @else
                        <span class="pl-path-media-emoji">{{ $path->icon ?: '🎓' }}</span>
                    @endif
                </div>
            @else
                {{-- Ikon unggahan, lalu emoji; sampul hanya dipakai bila jalur tak punya ikon sama sekali --}}
                <div class="pl-path-icon {{ $isList ? 'pl-path-icon-sm' : 'pl-path-icon-lg' }}">
                    @if($iconUrl)
                        <img src="{{ $iconUrl }}" alt="{{ $path->name }}" loading="lazy" class="pl-path-icon-img">
                    @elseif(filled($path->icon) || ! $coverUrl)
                        <span class="pl-path-icon-emoji">{{ $path->icon ?: '🎓' }}</span>
                    @else
                        <img src="{{ $coverUrl }}" alt="{{ $path->name }}" loading="lazy" class="pl-path-icon-cover">
                    @endif
                </div>
            @endif

            <div class="pl-path-body">
                <h3 class="pl-path-name" style="color:var(--text)">{{ $path->name }}</h3>
                @if($path->description)
                    <p class="pl-path-desc" style="color:var(--muted)">{{ $path->description }}</p>
                @endif
                <div class="pl-path-badges">
                    @forelse($path->institutions as $institution)
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">{{ $institution->short_name ?: $institution->name }}</span>
                    @empty
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">Semua jenjang</span>
                    @endforelse
                </div>
                @if($detailUrl && ! $isList)
                    <span class="pl-card-cta mt-auto inline-flex items-center gap-2 text-sm font-bold text-amber-600">
                        Lihat Selengkapnya
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </span>
                @endif
            </div>

            @if($detailUrl && $isList)
                <span class="pl-card-cta pl-path-row-arrow text-amber-600">
                    <span class="sr-only">Lihat Selengkapnya</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </span>
            @endif
        </{{ $tag }}>
    @endforeach
</div>
