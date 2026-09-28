<?php

namespace App\Filament\Resources\Institutions\Schemas;

use App\Filament\Support\IconUpload;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\SpmbRegistration;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InstitutionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Jenjang / Unit Pendidikan')
                ->description('Setiap jenjang menjalankan PPDB-nya sendiri: gelombang, jadwal, jalur, dan pendaftar.')
                ->icon('heroicon-o-building-library')
                ->schema([
                    Grid::make(12)->schema([
                        TextInput::make('icon')
                            ->label('Ikon')
                            ->maxLength(10)
                            ->placeholder('🎓')
                            ->hint('Emoji')
                            ->columnSpan(2),

                        TextInput::make('name')
                            ->label('Nama Jenjang')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
                            ->columnSpan(7),

                        TextInput::make('short_name')
                            ->label('Singkatan')
                            ->maxLength(20)
                            ->placeholder('SMA')
                            ->columnSpan(3),
                    ]),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->helperText('Dipakai di URL halaman PPDB (/ppdb/slug). Hindari mengubah bila sudah dipublikasikan.')
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull(),

                    TextInput::make('detail_url')
                        ->label('URL / Path Halaman Detail')
                        ->maxLength(500)
                        ->placeholder('https://sekolah.sch.id/profil/sma  atau  /profil/sma')
                        ->helperText('Opsional. Tautan halaman detail/profil jenjang agar calon pendaftar bisa melihat informasi lengkap sebelum mendaftar. Isi URL lengkap (https://...) atau path internal diawali garis miring (/...).')
                        ->columnSpanFull(),

                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(2)
                        ->maxLength(255)
                        ->columnSpanFull(),

                    IconUpload::make()
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        Select::make('color')
                            ->label('Warna Badge')
                            ->options([
                                'primary' => 'Primary',
                                'info' => 'Info (biru)',
                                'success' => 'Success (hijau)',
                                'warning' => 'Warning (kuning)',
                                'danger' => 'Danger (merah)',
                                'gray' => 'Gray',
                            ])
                            ->default('primary')
                            ->required()
                            ->native(false),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText('Nonaktifkan untuk menyembunyikan jenjang dari halaman PPDB publik.'),
                    ]),
                ]),

            Section::make('Mode Formulir Pendaftaran')
                ->description('Cara calon peserta jenjang ini mendaftar. Untuk mode eksternal/embed, data pendaftaran TIDAK disimpan di sistem ini.')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->schema([
                    Select::make('form_mode')
                        ->label('Mode Formulir')
                        ->options(Institution::formModeOptions())
                        ->default(Institution::FORM_MODE_INTERNAL)
                        ->required()
                        ->native(false)
                        ->live()
                        ->columnSpanFull(),

                    self::globalOverride(
                        'form_enabled',
                        'Pendaftaran Dibuka',
                        fn (): bool => setting_bool('spmb_form_enabled', true),
                        'Menutup jenjang ini saja tanpa menutup jenjang lain. Pesan penutupnya diatur di bagian Konten Halaman PPDB.',
                    )->columnSpanFull(),

                    TextInput::make('external_url')
                        ->label('URL Pendaftaran Eksternal')
                        ->url()
                        ->maxLength(500)
                        ->placeholder('https://ppdb.sekolahlain.sch.id')
                        ->helperText('Tombol "Daftar" akan mengarah ke tautan ini (dibuka di tab baru).')
                        ->required(fn (Get $get): bool => $get('form_mode') === Institution::FORM_MODE_EXTERNAL_LINK)
                        ->visible(fn (Get $get): bool => $get('form_mode') === Institution::FORM_MODE_EXTERNAL_LINK)
                        ->columnSpanFull(),

                    TextInput::make('embed_url')
                        ->label('URL Formulir untuk Disematkan')
                        ->url()
                        ->maxLength(500)
                        ->placeholder('https://docs.google.com/forms/d/e/.../viewform?embedded=true')
                        ->helperText('Formulir ditampilkan dalam bingkai (iframe). Google Form: Kirim → Sematkan < > → salin nilai src.')
                        ->required(fn (Get $get): bool => $get('form_mode') === Institution::FORM_MODE_EMBED)
                        ->visible(fn (Get $get): bool => $get('form_mode') === Institution::FORM_MODE_EMBED)
                        ->columnSpanFull(),

                    Toggle::make('show_status_button')
                        ->label('Tampilkan tombol "Cek Status & Pembayaran"')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Matikan untuk menyembunyikan tombol cek status pendaftaran dan tagihan pembayaran di halaman PPDB jenjang ini — misalnya bila pendaftarannya ditangani situs lain.')
                        ->columnSpanFull(),
                ]),

            Section::make('Kuota Penerimaan')
                ->description('Daya tampung jenjang ini untuk tahun ajaran aktif. Pendaftar berstatus Ditolak tidak dihitung, jadi menolak pendaftar membuka kembali slotnya.')
                ->icon('heroicon-o-user-group')
                ->schema([
                    TextInput::make('quota')
                        ->label('Kuota')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->suffix('kursi')
                        ->placeholder('Tanpa batas')
                        ->live(onBlur: true)
                        ->helperText(fn (?Institution $record): string => self::quotaUsageHint($record))
                        ->columnSpanFull(),

                    Toggle::make('close_when_full')
                        ->label('Tutup pendaftaran otomatis saat kuota penuh')
                        ->default(false)
                        ->onColor('success')
                        ->live()
                        ->visible(fn (Get $get): bool => $get('form_mode') === Institution::FORM_MODE_INTERNAL && filled($get('quota')))
                        ->helperText('Formulir tertutup sendiri begitu kuota terisi dan terbuka lagi bila ada pendaftar yang ditolak. Matikan bila kuota hanya sebagai informasi, misalnya tetap menerima daftar tunggu.')
                        ->columnSpanFull(),

                    Textarea::make('quota_full_message')
                        ->label('Pesan saat Kuota Penuh')
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder(fn (): string => (string) Setting::get('spmb_quota_full_message', '') ?: Institution::DEFAULT_QUOTA_FULL_MESSAGE)
                        ->helperText('Kosongkan untuk memakai pesan global dari Pengaturan PPDB.')
                        ->visible(fn (Get $get): bool => $get('form_mode') === Institution::FORM_MODE_INTERNAL && filled($get('quota')) && (bool) $get('close_when_full'))
                        ->columnSpanFull(),
                ]),

            Section::make('Biaya Pendaftaran')
                ->description('Penagihan biaya pendaftaran khusus jenjang ini. Setiap isian yang dikosongkan mengikuti Pengaturan PPDB global.')
                ->icon('heroicon-o-credit-card')
                ->schema([
                    Grid::make(3)->schema([
                        self::globalOverride(
                            'payment_enabled',
                            'Tagih Biaya Pendaftaran',
                            fn (): bool => setting_bool('spmb_payment_enabled', false),
                            'Bila aktif, pendaftar jenjang ini langsung menerima tagihan setelah mengirim formulir.',
                        ),

                        self::globalOverride(
                            'payment_unique_code',
                            'Pakai Kode Unik',
                            fn (): bool => setting_bool('spmb_payment_unique_code', true),
                            'Menambah 1–999 rupiah pada nominal agar transfer mudah dicocokkan di mutasi.',
                        ),

                        TextInput::make('payment_deadline_hours')
                            ->label('Batas Waktu Bayar (jam)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(720)
                            ->placeholder(fn (): string => 'Ikut global ('.(int) setting('spmb_payment_deadline_hours', 48).' jam)')
                            ->helperText('Isi 0 bila tagihan jenjang ini tidak pernah kedaluwarsa.'),
                    ]),

                    TextInput::make('registration_fee')
                        ->label('Nominal Biaya Pendaftaran yang Ditagih')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->placeholder('150000')
                        ->helperText('Kosongkan atau isi 0 bila pendaftaran jenjang ini gratis — tidak ada tagihan yang terbit. Rincian biaya lain (SPP, seragam) diatur di bagian Konten Halaman PPDB dan sifatnya hanya informasi.')
                        ->columnSpanFull(),

                    Repeater::make('bank_accounts')
                        ->label('Rekening Tujuan Jenjang Ini')
                        ->helperText('Rekening yang dipilih pendaftar saat mengunggah bukti transfer. Kosongkan bila jenjang ini memakai rekening global.')
                        ->schema([
                            Grid::make(12)->schema([
                                TextInput::make('bank')
                                    ->label('Bank')
                                    ->required()
                                    ->maxLength(40)
                                    ->placeholder('BSI')
                                    ->columnSpan(3),

                                TextInput::make('number')
                                    ->label('Nomor Rekening')
                                    ->required()
                                    ->maxLength(40)
                                    ->placeholder('7123456789')
                                    ->columnSpan(4),

                                TextInput::make('holder')
                                    ->label('Atas Nama')
                                    ->required()
                                    ->maxLength(80)
                                    ->placeholder('Yayasan Nurul Islam')
                                    ->columnSpan(5),
                            ]),
                        ])
                        ->addActionLabel('+ Tambah Rekening')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->maxItems(5)
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): string => trim(($state['bank'] ?? 'Rekening baru').' — '.($state['number'] ?? '')))
                        ->collapsible()
                        ->columnSpanFull(),

                    Textarea::make('payment_instructions')
                        ->label('Instruksi Pembayaran')
                        ->rows(3)
                        ->maxLength(600)
                        ->placeholder('Transfer sesuai nominal yang tertera (termasuk 3 digit terakhir), lalu unggah bukti transfer pada halaman ini.')
                        ->helperText('Tampil di atas formulir unggah bukti transfer. Kosongkan untuk memakai instruksi global.')
                        ->columnSpanFull(),
                ]),

            Section::make('Konten Halaman PPDB')
                ->description('Khusus jenjang ini. Kosongkan sebuah bagian untuk memakai pengaturan global (Pengaturan PPDB).')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('form_title')
                        ->label('Judul Formulir')
                        ->maxLength(120)
                        ->placeholder('Formulir Pendaftaran SPMB')
                        ->columnSpanFull(),

                    Textarea::make('form_description')
                        ->label('Deskripsi / Petunjuk Formulir')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('closed_message')
                        ->label('Pesan saat Pendaftaran Ditutup')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('success_message')
                        ->label('Keterangan setelah Pendaftaran Terkirim')
                        ->rows(3)
                        ->maxLength(600)
                        ->placeholder(SpmbRegistration::DEFAULT_SUCCESS_MESSAGE)
                        ->helperText('Tersedia: {nomor_pendaftaran}, {nama}, {jenjang}, {tahun_ajaran}. Bila jenjang ini menagih biaya pendaftaran, kalimat ajakan menyelesaikan pembayaran ditambahkan otomatis di belakangnya.')
                        ->columnSpanFull(),

                    Repeater::make('procedures')
                        ->label('Prosedur Pendaftaran')
                        ->schema([
                            Grid::make(12)->schema([
                                TextInput::make('icon')
                                    ->label('Ikon')
                                    ->maxLength(10)
                                    ->placeholder('📝')
                                    ->columnSpan(2),

                                TextInput::make('title')
                                    ->label('Judul Langkah')
                                    ->required()
                                    ->maxLength(60)
                                    ->columnSpan(10),

                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2)
                                    ->maxLength(300)
                                    ->columnSpanFull(),
                            ]),
                        ])
                        ->addActionLabel('+ Tambah Langkah')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->maxItems(10)
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): string => trim(($state['icon'] ?? '').' '.($state['title'] ?? 'Langkah')))
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),

                    Repeater::make('fees')
                        ->label('Rincian Biaya (informasi saja)')
                        ->schema([
                            Grid::make(12)->schema([
                                TextInput::make('category')
                                    ->label('Kategori')
                                    ->required()
                                    ->maxLength(60)
                                    ->columnSpan(4),

                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->required()
                                    ->maxLength(30)
                                    ->columnSpan(3),

                                TextInput::make('note')
                                    ->label('Keterangan')
                                    ->maxLength(100)
                                    ->columnSpan(5),
                            ]),
                        ])
                        ->addActionLabel('+ Tambah Biaya')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->maxItems(15)
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): string => trim(($state['category'] ?? 'Item').' — '.($state['amount'] ?? '')))
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),

                    Toggle::make('show_requirements')
                        ->label('Tampilkan Persyaratan Dokumen')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger')
                        ->live()
                        ->helperText('Matikan untuk menyembunyikan kartu persyaratan dokumen di halaman PPDB jenjang ini, meskipun daftar globalnya terisi.')
                        ->columnSpanFull(),

                    Repeater::make('requirements')
                        ->label('Persyaratan Dokumen')
                        ->visible(fn (Get $get): bool => (bool) $get('show_requirements'))
                        ->simple(
                            TextInput::make('requirement')
                                ->hiddenLabel()
                                ->required()
                                ->maxLength(200)
                                ->placeholder('Fotokopi Kartu Keluarga'),
                        )
                        ->addActionLabel('+ Tambah Persyaratan')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->maxItems(20)
                        ->defaultItems(0)
                        ->helperText('Kosongkan untuk memakai daftar persyaratan global.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * How full the quota is right now, so the panitia sees the effect of the
     * number they type before saving.
     */
    private static function quotaUsageHint(?Institution $record): string
    {
        $hint = 'Kosongkan bila tanpa batas.';

        if ($record === null || ! $record->usesInternalForm()) {
            return $hint;
        }

        return 'Saat ini terisi '.$record->quotaUsed().' pendaftar pada tahun ajaran '.spmb_year_label().'. '.$hint;
    }

    /**
     * A switch with three states: follow the global Pengaturan PPDB (null),
     * force on, or force off. A plain toggle cannot express "ikut global",
     * which is why this is a Select.
     *
     * @param  callable(): bool  $globalValue  Current global value, read lazily
     *                                         so the placeholder stays honest.
     */
    private static function globalOverride(string $name, string $label, callable $globalValue, ?string $helper = null): Select
    {
        return Select::make($name)
            ->label($label)
            ->options([1 => 'Aktif', 0 => 'Nonaktif'])
            ->placeholder(fn (): string => 'Ikut pengaturan global ('.($globalValue() ? 'Aktif' : 'Nonaktif').')')
            ->native(false)
            ->helperText($helper);
    }
}
