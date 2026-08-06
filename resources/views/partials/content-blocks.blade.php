{{--
    Perender blok konten. Satu berkas ini melayani dua bentuk tampilan:

      • `full`   — tiap blok berdiri sebagai seksi selebar layar dengan latarnya
                   sendiri, dipakai halaman & program tanpa sidebar
      • `boxed`  — seksi yang sama dikecilkan jadi kartu bersudut membulat,
                   dipakai halaman & program yang menyalakan sidebar kanan
      • `inline` — blok mengalir apa adanya di dalam kartu artikel (bawaan),
                   dipakai artikel, kegiatan, dan cerita

    Params:
      $blocks — array definisi blok (dari cast `blocks` milik model)
      $title  — teks alt/caption cadangan (judul halaman/artikel)
      $mode   — 'inline' (bawaan) | 'full' | 'boxed'
--}}
@php
    $title = $title ?? '';
    $mode = $mode ?? 'inline';
    $sectioned = $mode !== 'inline';
@endphp

@if(!empty($blocks))
    @foreach($blocks as $block)
        @php
            $eyebrow = trim((string) ($block['eyebrow'] ?? ''));
            $heading = trim((string) ($block['heading'] ?? ''));
            // Setiap seksi tetap punya target tautan walau anchornya tidak diisi
            $anchor = \Illuminate\Support\Str::slug((string) ($block['anchor'] ?? '')) ?: 'seksi-'.($loop->index + 1);
            $padding = $block['padding'] ?? 'md';
            $headingAlign = in_array($block['heading_align'] ?? 'left', ['left', 'center', 'right', 'justify'], true)
                ? $block['heading_align'] ?? 'left'
                : 'left';
        @endphp

        @if(! $sectioned)
            @include('partials.content-block-body', ['block' => $block, 'title' => $title, 'sectioned' => false])
        @else
            <x-section-background :config="\App\Support\SectionBackground::fromBlock($block, $mode === 'boxed' ? 'alt' : 'base')"
                                  id="{{ $anchor }}"
                                  class="block-section block-section-{{ $mode }} block-pad-{{ $padding }}">
                <div class="block-section-inner">
                    @if($eyebrow !== '' || $heading !== '')
                        <div class="block-section-head block-text-{{ $headingAlign }}">
                            @if($eyebrow !== '')
                                <div class="fi-label block-section-eyebrow">{{ $eyebrow }}</div>
                            @endif

                            @if($heading !== '')
                                <h2 class="block-section-title">{{ $heading }}</h2>
                            @endif
                        </div>
                    @endif

                    @include('partials.content-block-body', ['block' => $block, 'title' => $title, 'sectioned' => true])
                </div>
            </x-section-background>
        @endif
    @endforeach
@endif
