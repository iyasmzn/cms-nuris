{{--
    Isi judul hero: teksnya sendiri plus perangkat yang dibutuhkan efek
    pilihan admin.

    Semua efek CSS cukup dibungkus `.hero-title-text` agar garis bawah, stabilo,
    dan bingkainya memeluk teks, bukan seluruh lebar kolom. Hanya mesin ketik
    yang perlu JavaScript — dan ia mengetik ulang tiap kali slide-nya kembali
    tampil, jadi ia ikut menonton `slide` milik state Alpine di <body>.

    @param  \App\Support\HeroTitleEffect  $heroTitle  Efek milik slide ini
    @param  string  $title
    @param  int  $index  Nomor slide pemilik judul
--}}
@php
    $isTyping = $heroTitle->isTyping();
@endphp

@if($isTyping)
    @once
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('heroTitleTyping', (full, speed, hold, loop) => ({
                    full, speed, hold, loop,
                    typed: '',
                    timer: null,

                    start() {
                        this.stop();

                        // Pengunjung yang meminta gerak seminimal mungkin
                        // langsung mendapat judul utuh.
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                            this.typed = this.full;
                            return;
                        }

                        this.step(0, false);
                    },

                    stop() {
                        clearTimeout(this.timer);
                        this.timer = null;
                    },

                    /** Satu huruf per panggilan, maju saat mengetik dan mundur saat menghapus. */
                    step(index, erasing) {
                        this.typed = this.full.slice(0, index);

                        if (! erasing && index >= this.full.length) {
                            if (! this.loop) {
                                return;
                            }

                            this.timer = setTimeout(() => this.step(index, true), this.hold);

                            return;
                        }

                        if (erasing && index <= 0) {
                            this.timer = setTimeout(() => this.step(0, false), this.speed * 2);

                            return;
                        }

                        this.timer = setTimeout(
                            () => this.step(erasing ? index - 1 : index + 1, erasing),
                            erasing ? Math.max(18, this.speed / 2) : this.speed,
                        );
                    },

                    destroy() {
                        this.stop();
                    },
                }));
            });
        </script>
    @endonce
@endif

<span class="hero-title-text"
      @if($isTyping)
          x-data="heroTitleTyping({{ Illuminate\Support\Js::from($title) }}, {{ $heroTitle->typingSpeedFor($title) }}, {{ $heroTitle->typingHoldMs() }}, {{ $heroTitle->loop ? 'true' : 'false' }})"
          x-init="$watch('slide', value => value === {{ $index }} ? start() : stop()); if (slide === {{ $index }}) start()"
      @endif
>@if($isTyping)<span x-text="typed">{{ $title }}</span>@else{{ $title }}@endif@if($heroTitle->showsCaret())<span class="hero-title-caret" aria-hidden="true"></span>@endif</span>
