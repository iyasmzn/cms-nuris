{{--
    Kerangka tata letak halaman publik — pengaturan global "Lebar Maksimum Isi
    Halaman" dan "Margin Kiri & Kanan" di Tema & Tampilan. Nilainya dipasang
    sebagai `--page-max-width` dan `--page-gutter` (per ukuran layar), lalu
    menimpa pembungkus konten baku (`max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`)
    di seluruh halaman publik sekaligus.
--}}
<style>
{!! page_max_width_css() !!}
{!! page_gutter_css() !!}
</style>
