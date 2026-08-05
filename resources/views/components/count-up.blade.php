@props(['value', 'duration' => 1600])

@php
    /**
     * Nilai statistik ditulis bebas oleh admin ("3.500+", "92%", "1992"),
     * jadi angkanya dipisah dulu dari awalan/akhiran supaya hanya bagian
     * angka yang dihitung naik dan format aslinya tetap utuh.
     *
     * @var array{0: string, 1: int}|null $match
     */
    $raw = trim((string) $value);
    $number = null;

    if (preg_match('/-?\d+(?:[.,]\d+)*/', $raw, $matches, PREG_OFFSET_CAPTURE)) {
        [$number, $offset] = $matches[0];

        $prefix = substr($raw, 0, $offset);
        $suffix = substr($raw, $offset + strlen($number));

        $lastDot = strrpos($number, '.');
        $lastComma = strrpos($number, ',');
        $groupSeparator = '';
        $decimalSeparator = '';

        if ($lastDot !== false && $lastComma !== false) {
            /** Dua pemisah sekaligus: yang paling belakang pasti desimal. */
            $decimalSeparator = $lastDot > $lastComma ? '.' : ',';
            $groupSeparator = $decimalSeparator === '.' ? ',' : '.';
        } elseif ($lastDot !== false || $lastComma !== false) {
            $separator = $lastDot !== false ? '.' : ',';
            $tail = substr($number, strrpos($number, $separator) + 1);

            /** "3.500" dibaca ribuan, "9,5" dibaca desimal. */
            if (substr_count($number, $separator) > 1 || strlen($tail) === 3) {
                $groupSeparator = $separator;
            } else {
                $decimalSeparator = $separator;
            }
        }

        $decimals = $decimalSeparator === ''
            ? 0
            : strlen(substr($number, strrpos($number, $decimalSeparator) + 1));

        $plain = $number;

        if ($groupSeparator !== '') {
            $plain = str_replace($groupSeparator, '', $plain);
        }

        if ($decimalSeparator !== '') {
            $plain = str_replace($decimalSeparator, '.', $plain);
        }

        $target = (float) $plain;
    }
@endphp

@if($number === null || $target == 0.0)
    {{-- Tanpa angka yang bisa dihitung (mis. "-" atau "Segera"), tampilkan apa adanya. --}}
    <span {{ $attributes }}>{{ $raw }}</span>
@else
    <span {{ $attributes->merge(['style' => 'font-variant-numeric:tabular-nums']) }}
          x-data="countUp({
              target: {{ $target }},
              decimals: {{ $decimals }},
              group: @js($groupSeparator),
              decimal: @js($decimalSeparator ?: ','),
              prefix: @js($prefix),
              suffix: @js($suffix),
              duration: {{ (int) $duration }},
          })"
          x-init="start()"
          x-text="text">{{ $raw }}</span>

    @once
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('countUp', (config) => ({
                    ...config,
                    text: '',

                    /** Susun ulang angka mengikuti format aslinya (pemisah ribuan & desimal). */
                    format(value) {
                        const fixed = Math.abs(value).toFixed(this.decimals)
                        let [whole, fraction] = fixed.split('.')

                        if (this.group) {
                            whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, this.group)
                        }

                        return this.prefix
                            + (value < 0 ? '-' : '')
                            + whole
                            + (fraction ? this.decimal + fraction : '')
                            + this.suffix
                    },

                    start() {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                            this.text = this.format(this.target)

                            return
                        }

                        this.text = this.format(0)

                        /* Hitungan baru berjalan saat kartunya benar-benar terlihat,
                           supaya animasinya tidak terlewat saat pengunjung menggulir. */
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach((entry) => {
                                if (! entry.isIntersecting) {
                                    return
                                }

                                observer.disconnect()
                                this.run()
                            })
                        }, { threshold: 0.4 })

                        observer.observe(this.$el)
                    },

                    run() {
                        const startedAt = performance.now()

                        const step = (now) => {
                            const progress = Math.min((now - startedAt) / this.duration, 1)
                            /* easeOutCubic: cepat di awal lalu melambat mendekati angka akhir. */
                            const eased = 1 - Math.pow(1 - progress, 3)

                            this.text = this.format(this.target * eased)

                            if (progress < 1) {
                                requestAnimationFrame(step)
                            } else {
                                this.text = this.format(this.target)
                            }
                        }

                        requestAnimationFrame(step)
                    },
                }))
            })
        </script>
    @endonce
@endif
