{{--
    Pita kepala halaman — hero beranda, hero tiap subhalaman, kepala halaman
    statis, sampai layar masuk/daftar — tampil sebagai blok membulat yang
    menjorok dari tepi layar. Warnanya mengikuti Warna Utama di Tema &
    Tampilan; polanya dipasang lewat komponen hero-pattern.

    Dipakai bersama oleh welcome.blade.php dan layouts/public.blade.php.
--}}
<style>
    :root {
        /* Pita gelap di kepala tiap subhalaman — mengikuti warna utama
           pilihan admin, bukan warna tetap. */
        --hero-band: linear-gradient(135deg,
            color-mix(in oklab, var(--primary) 45%, black) 0%,
            var(--primary) 60%,
            color-mix(in oklab, var(--primary) 78%, white) 100%);

        /* Jarak antara tepi layar dan pita yang membulat */
        --shell-gap: .875rem;
        --band-radius: 2.5rem;
    }

    /* Tingginya tetap ditentukan masing-masing halaman; yang dibagikan di sini
       hanya bentuknya. `position: relative` menjadi jangkar lapisan pola yang
       duduk di dalam pita. */
    .page-band {
        border-radius: 0 0 var(--band-radius) var(--band-radius);
        overflow: hidden;
        position: relative;
    }

    @media (min-width: 768px) {
        .page-band {
            margin-inline: var(--shell-gap);
            border-radius: var(--band-radius);
        }

        /* Pita menjorok dari keempat sisi; navbar ikut turun bersamanya karena
           berada di aliran dokumen di atas pita. Tambahan 4px menutup selisih
           antara tarikan pita (`-mt-17` = 68px) dan tinggi navbar (`h-16` =
           64px), sehingga jarak atas sama persis dengan jarak kiri-kanan. */
        body.page-inset { padding-top: calc(var(--shell-gap) + 4px); }
    }

    /* ── Judul pita — tebal, rapat, dan mengecil mulus lewat clamp() ── */
    .display-title {
        font-size: clamp(2.25rem, 1.15rem + 3.7vw, 4.25rem);
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.06;
    }
    .section-title {
        font-size: clamp(1.875rem, 1.15rem + 2.4vw, 3.25rem);
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.12;
    }
</style>
