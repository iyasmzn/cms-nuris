<?php

namespace App\Filament\Resources\SpmbRegistrations\Schemas;

use App\Models\PpdbField;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SpmbRegistrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Pendaftaran')
                ->icon('heroicon-o-clipboard-document-list')
                ->columns(3)
                ->schema([
                    TextEntry::make('registration_number')
                        ->label('No. Pendaftaran')
                        ->badge()
                        ->color('gray')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'verified' => 'info',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => SpmbRegistration::statusOptions()[$state] ?? $state),

                    TextEntry::make('created_at')
                        ->label('Tgl. Daftar')
                        ->dateTime('d M Y, H:i'),

                    TextEntry::make('institution.name')
                        ->label('Jenjang')
                        ->placeholder('—'),

                    TextEntry::make('academicYear.label')
                        ->label('Tahun Ajaran')
                        ->placeholder('—'),

                    TextEntry::make('registrationWave.name')
                        ->label('Gelombang')
                        ->placeholder('—'),

                    TextEntry::make('admissionPath.name')
                        ->label('Jalur')
                        ->badge()
                        ->color(fn (SpmbRegistration $record): string => $record->admissionPath?->color ?? 'gray')
                        ->placeholder('—'),

                    TextEntry::make('verified_at')
                        ->label('Tgl. Verifikasi')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('—')
                        ->columnSpan(2),
                ]),

            Section::make('Data Calon Peserta')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    TextEntry::make('full_name')->label('Nama Lengkap')->weight('bold'),
                    TextEntry::make('nik')->label('NIK')->copyable()->placeholder('—'),
                    TextEntry::make('phone')->label('No. HP / WhatsApp')->copyable(),
                    TextEntry::make('email')->label('Email')->copyable()->placeholder('—'),
                    TextEntry::make('birth_place')->label('Tempat Lahir')->placeholder('—'),
                    TextEntry::make('birth_date')->label('Tanggal Lahir')->date('d M Y')->placeholder('—'),
                    TextEntry::make('previous_school')->label('Sekolah Asal'),
                    TextEntry::make('previous_school_city')->label('Kota Sekolah')->placeholder('—'),
                    TextEntry::make('address')->label('Alamat')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Orang Tua / Wali')
                ->icon('heroicon-o-users')
                ->columns(2)
                ->schema([
                    TextEntry::make('parent_name')->label('Nama')->placeholder('—'),
                    TextEntry::make('parent_phone')->label('No. HP')->copyable()->placeholder('—'),
                ]),

            Section::make('Pembayaran')
                ->description('Ringkasan tagihan biaya pendaftaran. Tindakan verifikasi tersedia di tombol atas halaman ini.')
                ->icon('heroicon-o-credit-card')
                ->visible(fn (SpmbRegistration $record): bool => $record->payment !== null)
                ->columns(3)
                ->schema([
                    TextEntry::make('payment.invoice_number')
                        ->label('No. Tagihan')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    TextEntry::make('payment.status')
                        ->label('Status Pembayaran')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            RegistrationPayment::STATUS_UNPAID => 'warning',
                            RegistrationPayment::STATUS_WAITING => 'info',
                            RegistrationPayment::STATUS_PAID => 'success',
                            RegistrationPayment::STATUS_REJECTED => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => RegistrationPayment::statusOptions()[$state] ?? $state),

                    TextEntry::make('payment.total')
                        ->label('Total Ditagihkan')
                        ->state(fn (SpmbRegistration $record): string => rupiah($record->payment?->total()))
                        ->helperText(fn (SpmbRegistration $record): ?string => ($record->payment?->unique_code ?? 0) > 0
                            ? rupiah($record->payment->amount).' + kode unik '.$record->payment->unique_code
                            : null)
                        ->weight('bold'),

                    TextEntry::make('payment.sender_name')
                        ->label('Nama Pengirim')
                        ->placeholder('Belum ada bukti'),

                    TextEntry::make('payment.transferred_on')
                        ->label('Tgl. Transfer')
                        ->date('d M Y')
                        ->placeholder('—'),

                    TextEntry::make('payment.bank_account')
                        ->label('Rekening Tujuan')
                        ->placeholder('—'),

                    TextEntry::make('payment.submitted_at')
                        ->label('Bukti Dikirim')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('—'),

                    TextEntry::make('payment.expires_at')
                        ->label('Batas Waktu Bayar')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('—'),

                    TextEntry::make('payment.verifier.name')
                        ->label('Diverifikasi Oleh')
                        ->placeholder('—'),

                    ViewEntry::make('payment_proof')
                        ->label('Bukti Transfer')
                        ->view('filament.infolists.payment-proof')
                        ->columnSpanFull(),

                    TextEntry::make('payment.note')
                        ->label('Catatan Verifikasi')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Catatan Panitia')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (SpmbRegistration $record): bool => filled($record->notes))
                ->schema([
                    TextEntry::make('notes')->hiddenLabel()->columnSpanFull(),
                ]),

            Section::make('Data Tambahan')
                ->description('Nilai field kustom di luar kolom baku, diisi lewat formulir dinamis.')
                ->icon('heroicon-o-list-bullet')
                ->collapsed()
                ->visible(fn (SpmbRegistration $record): bool => filled($record->data))
                ->schema([
                    KeyValueEntry::make('data')
                        ->hiddenLabel()
                        ->keyLabel('Field')
                        ->valueLabel('Nilai')
                        ->columnSpanFull(),
                ]),

            Section::make('Berkas Terunggah')
                ->icon('heroicon-o-paper-clip')
                ->visible(fn (SpmbRegistration $record): bool => (bool) $record->institution?->ppdbFields()->where('type', 'file')->exists())
                ->schema([
                    TextEntry::make('berkas')
                        ->hiddenLabel()
                        ->state(function (SpmbRegistration $record): HtmlString {
                            $links = ($record->institution?->ppdbFields()
                                ->where('type', 'file')
                                ->orderBy('sort_order')
                                ->get() ?? collect())
                                ->filter(fn (PpdbField $field): bool => filled(data_get($record->data, $field->key)))
                                ->map(fn (PpdbField $field): string => '<a href="'.e(route('ppdb.berkas', [$record, $field->key])).'" target="_blank" class="text-primary-600 underline">📎 '.e($field->label).'</a>')
                                ->implode('<br>');

                            return new HtmlString($links ?: '<span class="text-gray-500">Belum ada berkas diunggah.</span>');
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
