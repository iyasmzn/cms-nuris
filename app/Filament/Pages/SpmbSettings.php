<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Institutions\InstitutionResource;
use App\Filament\Support\IconUpload;
use App\Models\Institution;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class SpmbSettings extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.general-settings';

    protected static string|UnitEnum|null $navigationGroup = 'PPDB / SPMB';

    protected static ?string $navigationLabel = 'Pengaturan PPDB';

    protected static ?string $title = 'Pengaturan PPDB / SPMB';

    protected static ?int $navigationSort = 2;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $procedures = json_decode(Setting::get('spmb_procedures', ''), true);
        $fees = json_decode(Setting::get('spmb_fees', ''), true);
        $bankAccounts = json_decode(Setting::get('spmb_bank_accounts', ''), true);

        $this->form->fill([
            // Konten kartu SPMB di halaman depan
            'spmb_card_title' => Setting::get('spmb_card_title', 'SPMB Tahun Ajaran {year} Dibuka!'),
            'spmb_card_description' => Setting::get('spmb_card_description', 'Pendaftaran peserta didik baru resmi dibuka. Tersedia jalur Prestasi, Zonasi, dan Afirmasi. Segera lengkapi berkas dan daftarkan diri Anda sebelum batas waktu.'),
            'spmb_card_cta_label' => Setting::get('spmb_card_cta_label', 'Daftar Sekarang'),
            'spmb_card_cta_url' => Setting::get('spmb_card_cta_url', '/ppdb'),
            'spmb_card_secondary_label' => Setting::get('spmb_card_secondary_label', 'Info Selengkapnya'),

            // Konten section tahapan di halaman depan
            'spmb_steps_title' => Setting::get('spmb_steps_title', 'Tahapan SPMB'),
            'spmb_steps_description' => Setting::get('spmb_steps_description', 'Ikuti langkah-langkah berikut untuk mendaftarkan diri sebagai calon peserta didik baru.'),
            'spmb_steps_cta_label' => Setting::get('spmb_steps_cta_label', 'Lihat Detail & Daftar'),
            'spmb_steps_cta_url' => Setting::get('spmb_steps_cta_url', '/ppdb'),

            // Form toggle & teks
            'spmb_form_enabled' => (bool) Setting::get('spmb_form_enabled', true),
            'spmb_form_title' => Setting::get('spmb_form_title', 'Formulir Pendaftaran SPMB'),
            'spmb_form_description' => Setting::get('spmb_form_description', 'Isi formulir di bawah ini dengan data yang benar dan lengkap. Panitia akan menghubungi Anda untuk proses verifikasi.'),
            'spmb_closed_message' => Setting::get('spmb_closed_message', 'Pendaftaran SPMB saat ini sedang ditutup. Pantau informasi terbaru melalui halaman ini.'),

            // Prosedur
            'procedures' => is_array($procedures) ? $procedures : $this->defaultProcedures(),

            // Biaya
            'fees' => is_array($fees) ? $fees : $this->defaultFees(),

            // Pembayaran biaya pendaftaran
            'spmb_payment_enabled' => (bool) Setting::get('spmb_payment_enabled', false),
            'spmb_payment_unique_code' => (bool) Setting::get('spmb_payment_unique_code', true),
            'spmb_payment_deadline_hours' => (int) Setting::get('spmb_payment_deadline_hours', 48),
            'spmb_payment_instructions' => Setting::get('spmb_payment_instructions', 'Transfer sesuai nominal yang tertera (termasuk 3 digit terakhir) ke salah satu rekening di bawah, lalu unggah bukti transfer pada halaman ini.'),
            'bank_accounts' => is_array($bankAccounts) ? $bankAccounts : [],
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Kartu SPMB — Halaman Depan')
                ->description('Teks dan tombol pada kartu SPMB besar di halaman depan. Gunakan {year} untuk menyisipkan tahun ajaran secara otomatis.')
                ->icon(Heroicon::OutlinedHome)
                ->schema([
                    TextInput::make('spmb_card_title')
                        ->label('Judul Kartu')
                        ->maxLength(120)
                        ->placeholder('SPMB Tahun Ajaran {year} Dibuka!')
                        ->hint('Gunakan {year} untuk tahun ajaran otomatis')
                        ->columnSpanFull(),

                    Textarea::make('spmb_card_description')
                        ->label('Deskripsi')
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        TextInput::make('spmb_card_cta_label')
                            ->label('Label Tombol Utama')
                            ->maxLength(40)
                            ->placeholder('Daftar Sekarang'),

                        TextInput::make('spmb_card_cta_url')
                            ->label('URL Tombol Utama')
                            ->maxLength(200)
                            ->placeholder('/ppdb'),

                        TextInput::make('spmb_card_secondary_label')
                            ->label('Label Tombol Kedua')
                            ->maxLength(40)
                            ->placeholder('Info Selengkapnya')
                            ->hint('URL tombol kedua selalu menuju /ppdb'),
                    ]),
                ]),

            Section::make('Tahapan SPMB — Halaman Depan')
                ->description('Judul, deskripsi, dan tombol pada section tahapan SPMB di halaman depan. Langkah-langkah diambil dari Prosedur Pendaftaran di bawah.')
                ->icon(Heroicon::OutlinedListBullet)
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('spmb_steps_title')
                            ->label('Judul Section')
                            ->maxLength(80)
                            ->placeholder('Tahapan SPMB'),

                        TextInput::make('spmb_steps_description')
                            ->label('Deskripsi')
                            ->maxLength(200)
                            ->placeholder('Ikuti langkah-langkah berikut...'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('spmb_steps_cta_label')
                            ->label('Label Tombol CTA')
                            ->maxLength(40)
                            ->placeholder('Lihat Detail & Daftar'),

                        TextInput::make('spmb_steps_cta_url')
                            ->label('URL Tombol CTA')
                            ->maxLength(200)
                            ->placeholder('/ppdb'),
                    ]),
                ]),

            Section::make('Pengaturan Form Pendaftaran')
                ->description('Kelola teks dan status buka/tutup form pendaftaran SPMB online.')
                ->icon(Heroicon::OutlinedDocumentText)
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('spmb_form_enabled')
                            ->label('Form Pendaftaran Aktif')
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText('Nonaktifkan untuk menutup sementara form pendaftaran.'),

                        TextInput::make('spmb_form_title')
                            ->label('Judul Form')
                            ->maxLength(100),
                    ]),

                    Textarea::make('spmb_form_description')
                        ->label('Deskripsi / Petunjuk Form')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('spmb_closed_message')
                        ->label('Pesan saat Form Ditutup')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Prosedur Pendaftaran')
                ->description('Langkah-langkah yang tampil di halaman PPDB. Seret untuk mengubah urutan.')
                ->icon(Heroicon::OutlinedListBullet)
                ->schema([
                    Repeater::make('procedures')
                        ->label('')
                        ->schema([
                            Grid::make(12)->schema([
                                TextInput::make('icon')
                                    ->label('Ikon')
                                    ->maxLength(10)
                                    ->placeholder('📝')
                                    ->hint('Emoji')
                                    ->columnSpan(2),

                                TextInput::make('title')
                                    ->label('Judul Langkah')
                                    ->required()
                                    ->maxLength(60)
                                    ->placeholder('Isi Formulir Online')
                                    ->columnSpan(10),

                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2)
                                    ->maxLength(300)
                                    ->columnSpanFull(),

                                IconUpload::make()
                                    ->columnSpanFull(),
                            ]),
                        ])
                        ->addActionLabel('+ Tambah Langkah')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->maxItems(10)
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): string => trim(($state['icon'] ?? '').' '.($state['title'] ?? 'Langkah baru')))
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ]),

            Section::make('Biaya Pendaftaran')
                ->description('Daftar biaya yang tampil di halaman PPDB. Kosongkan jika gratis.')
                ->icon(Heroicon::OutlinedBanknotes)
                ->schema([
                    Repeater::make('fees')
                        ->label('')
                        ->schema([
                            Grid::make(12)->schema([
                                TextInput::make('category')
                                    ->label('Kategori')
                                    ->required()
                                    ->maxLength(60)
                                    ->placeholder('Biaya Pendaftaran')
                                    ->columnSpan(4),

                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->required()
                                    ->maxLength(30)
                                    ->placeholder('Rp 150.000')
                                    ->columnSpan(3),

                                TextInput::make('note')
                                    ->label('Keterangan')
                                    ->maxLength(100)
                                    ->placeholder('Dibayar saat daftar ulang')
                                    ->columnSpan(5),
                            ]),
                        ])
                        ->addActionLabel('+ Tambah Biaya')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->maxItems(15)
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): string => trim(($state['category'] ?? 'Item baru').' — '.($state['amount'] ?? '')))
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ]),

            Section::make('Pembayaran Biaya Pendaftaran')
                ->description('Aktifkan untuk menagih biaya pendaftaran lewat transfer manual. Nominal yang ditagih diatur per jenjang di menu Jenjang / Unit, bukan di daftar biaya informatif di atas.')
                ->icon(Heroicon::OutlinedCreditCard)
                ->schema([
                    Toggle::make('spmb_payment_enabled')
                        ->label('Aktifkan Tagihan Pendaftaran')
                        ->onColor('success')
                        ->offColor('danger')
                        ->live()
                        ->helperText('Saat aktif, setiap pendaftar pada jenjang yang punya nominal biaya akan menerima tagihan dan halaman unggah bukti transfer.')
                        ->columnSpanFull(),

                    Placeholder::make('payment_setup_status')
                        ->hiddenLabel()
                        ->visible(fn (Get $get): bool => (bool) $get('spmb_payment_enabled'))
                        ->content(fn (): HtmlString => $this->paymentSetupStatus())
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->visible(fn (Get $get): bool => (bool) $get('spmb_payment_enabled'))
                        ->schema([
                            Toggle::make('spmb_payment_unique_code')
                                ->label('Pakai Kode Unik 3 Digit')
                                ->onColor('success')
                                ->helperText('Menambahkan 1–999 rupiah pada nominal agar transfer mudah dicocokkan di mutasi rekening.'),

                            TextInput::make('spmb_payment_deadline_hours')
                                ->label('Batas Waktu Pembayaran (jam)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(720)
                                ->default(48)
                                ->helperText('Dihitung sejak formulir dikirim. Isi 0 untuk tanpa batas waktu.'),
                        ]),

                    Textarea::make('spmb_payment_instructions')
                        ->label('Petunjuk Pembayaran')
                        ->rows(3)
                        ->maxLength(600)
                        ->visible(fn (Get $get): bool => (bool) $get('spmb_payment_enabled'))
                        ->columnSpanFull(),

                    Repeater::make('bank_accounts')
                        ->label('Rekening Tujuan')
                        ->visible(fn (Get $get): bool => (bool) $get('spmb_payment_enabled'))
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
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::setMany([
            // Kartu halaman depan
            'spmb_card_title' => $data['spmb_card_title'] ?? '',
            'spmb_card_description' => $data['spmb_card_description'] ?? '',
            'spmb_card_cta_label' => $data['spmb_card_cta_label'] ?? 'Daftar Sekarang',
            'spmb_card_cta_url' => $data['spmb_card_cta_url'] ?? '/ppdb',
            'spmb_card_secondary_label' => $data['spmb_card_secondary_label'] ?? 'Info Selengkapnya',

            // Section tahapan halaman depan
            'spmb_steps_title' => $data['spmb_steps_title'] ?? '',
            'spmb_steps_description' => $data['spmb_steps_description'] ?? '',
            'spmb_steps_cta_label' => $data['spmb_steps_cta_label'] ?? 'Lihat Detail & Daftar',
            'spmb_steps_cta_url' => $data['spmb_steps_cta_url'] ?? '/ppdb',

            // Form
            'spmb_form_enabled' => (int) ($data['spmb_form_enabled'] ?? true),
            'spmb_form_title' => $data['spmb_form_title'] ?? '',
            'spmb_form_description' => $data['spmb_form_description'] ?? '',
            'spmb_closed_message' => $data['spmb_closed_message'] ?? '',

            // Konten
            'spmb_procedures' => json_encode(array_values($data['procedures'] ?? [])),
            'spmb_fees' => json_encode(array_values($data['fees'] ?? [])),

            // Pembayaran
            'spmb_payment_enabled' => (int) ($data['spmb_payment_enabled'] ?? false),
            'spmb_payment_unique_code' => (int) ($data['spmb_payment_unique_code'] ?? true),
            'spmb_payment_deadline_hours' => (int) ($data['spmb_payment_deadline_hours'] ?? 48),
            'spmb_payment_instructions' => $data['spmb_payment_instructions'] ?? '',
            'spmb_bank_accounts' => json_encode(array_values($data['bank_accounts'] ?? [])),
        ]);

        Notification::make()
            ->success()
            ->title('Pengaturan PPDB berhasil disimpan')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),
        ];
    }

    /**
     * Tagihan only ever get issued for a jenjang that has its own nominal, and
     * that field lives on the Jenjang / Unit resource — not on this page. When
     * the toggle is on but no jenjang carries a nominal, nothing happens at all
     * and there is otherwise no feedback anywhere explaining why, so spell out
     * exactly which jenjang are still missing it and where to fill it in.
     */
    private function paymentSetupStatus(): HtmlString
    {
        $jenjang = Institution::query()->active()->ordered()->get();
        $missing = $jenjang->filter(fn (Institution $unit): bool => (int) ($unit->registration_fee ?? 0) <= 0);

        $link = static function (Institution $unit): string {
            return '<a href="'.e(InstitutionResource::getUrl('edit', ['record' => $unit])).'" class="underline font-semibold">'.e($unit->short_name ?: $unit->name).'</a>';
        };

        if ($missing->isEmpty()) {
            $summary = $jenjang
                ->map(fn (Institution $unit): string => e($unit->short_name ?: $unit->name).' '.e(rupiah($unit->registration_fee)))
                ->implode(' · ');

            return new HtmlString(
                '<div class="rounded-lg border border-success-300 bg-success-50 p-4 text-sm text-success-700 dark:border-success-800 dark:bg-success-950 dark:text-success-300">'
                .'<strong>Siap.</strong> Semua jenjang aktif sudah punya nominal biaya pendaftaran.<br><span class="text-xs">'.$summary.'</span>'
                .'</div>'
            );
        }

        $isBlocked = $missing->count() === $jenjang->count();
        $tone = $isBlocked
            ? 'border-danger-300 bg-danger-50 text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-300'
            : 'border-warning-300 bg-warning-50 text-warning-700 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-300';

        $headline = $isBlocked
            ? 'Tagihan belum akan terbit sama sekali.'
            : 'Sebagian jenjang belum menagih biaya.';

        return new HtmlString(
            '<div class="rounded-lg border '.$tone.' p-4 text-sm">'
            .'<strong>'.$headline.'</strong> Nominal biaya diatur per jenjang, bukan di halaman ini. '
            .'Buka jenjang berikut lalu isi <em>Nominal Biaya Pendaftaran yang Ditagih</em> pada bagian "Konten Halaman PPDB": '
            .$missing->map($link)->implode(', ').'.'
            .'</div>'
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultProcedures(): array
    {
        return [
            ['icon' => '📝', 'title' => 'Isi Formulir Online', 'description' => 'Kunjungi halaman PPDB dan isi formulir pendaftaran secara lengkap dan benar.'],
            ['icon' => '📁', 'title' => 'Siapkan Berkas', 'description' => 'Persiapkan dokumen yang diperlukan: ijazah/SHUN, rapor kelas 7-9, dan pas foto terbaru.'],
            ['icon' => '✅', 'title' => 'Verifikasi Berkas', 'description' => 'Datang ke sekolah untuk verifikasi berkas pada tanggal yang telah ditentukan.'],
            ['icon' => '🎉', 'title' => 'Pengumuman Hasil', 'description' => 'Hasil seleksi diumumkan melalui halaman resmi sekolah dan via WhatsApp/email.'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultFees(): array
    {
        return [
            ['category' => 'Biaya Pendaftaran', 'amount' => 'Rp 0', 'note' => 'Gratis'],
            ['category' => 'Seragam Sekolah', 'amount' => 'Rp 500.000', 'note' => '3 stel seragam'],
            ['category' => 'Buku Paket', 'amount' => 'Rp 350.000', 'note' => 'Per semester'],
        ];
    }
}
