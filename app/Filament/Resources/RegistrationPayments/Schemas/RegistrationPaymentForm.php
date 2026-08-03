<?php

namespace App\Filament\Resources\RegistrationPayments\Schemas;

use App\Models\RegistrationPayment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RegistrationPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Tagihan')
                ->icon('heroicon-o-credit-card')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('invoice_number')
                            ->label('Nomor Tagihan')
                            ->content(fn (?RegistrationPayment $record): string => $record?->invoice_number ?? '—'),

                        Placeholder::make('registration')
                            ->label('Pendaftar')
                            ->content(fn (?RegistrationPayment $record): string => $record?->registration?->full_name ?? '—')
                            ->hint(fn (?RegistrationPayment $record): ?string => $record?->registration?->registration_number),

                        Placeholder::make('institution')
                            ->label('Jenjang')
                            ->content(fn (?RegistrationPayment $record): string => $record?->registration?->institution?->name ?? '—'),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('amount')
                            ->label('Nominal Biaya')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('Rp'),

                        TextInput::make('unique_code')
                            ->label('Kode Unik')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(999)
                            ->helperText('0 berarti tanpa kode unik.'),

                        Placeholder::make('total')
                            ->label('Total Ditagihkan')
                            ->content(fn (?RegistrationPayment $record): string => $record === null ? '—' : rupiah($record->total())),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label('Status Pembayaran')
                            ->options(RegistrationPayment::statusOptions())
                            ->required()
                            ->native(false)
                            ->helperText('Ubah manual hanya bila perlu koreksi. Gunakan tombol Lunas / Tolak di daftar untuk alur normal.'),

                        DateTimePicker::make('expires_at')
                            ->label('Batas Waktu Pembayaran')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Kosongkan untuk menghapus batas waktu.'),
                    ]),
                ]),

            Section::make('Bukti Transfer')
                ->icon('heroicon-o-paper-clip')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('sender_name')
                            ->label('Nama Pengirim')
                            ->maxLength(100),

                        DatePicker::make('transferred_on')
                            ->label('Tanggal Transfer')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Placeholder::make('bank_account')
                            ->label('Rekening Tujuan')
                            ->content(fn (?RegistrationPayment $record): string => $record?->bank_account ?? '—'),
                    ]),

                    Placeholder::make('proof')
                        ->label('Berkas Bukti')
                        ->content(fn (?RegistrationPayment $record): HtmlString => new HtmlString(
                            filled($record?->proof_path)
                                ? '<a href="'.e(route('ppdb.payment.download', $record)).'" target="_blank" class="text-primary-600 underline">📎 Unduh bukti transfer</a>'
                                : '<span class="text-gray-500">Belum ada bukti diunggah.</span>'
                        ))
                        ->columnSpanFull(),
                ]),

            Section::make('Verifikasi')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('verified_at')
                            ->label('Waktu Verifikasi')
                            ->content(fn (?RegistrationPayment $record): string => $record?->verified_at?->translatedFormat('d M Y, H:i') ?? '—'),

                        Placeholder::make('verifier')
                            ->label('Diverifikasi Oleh')
                            ->content(fn (?RegistrationPayment $record): string => $record?->verifier?->name ?? '—'),
                    ]),

                    Textarea::make('note')
                        ->label('Catatan Panitia')
                        ->rows(3)
                        ->helperText('Bila status "Bukti ditolak", catatan ini ditampilkan ke pendaftar sebagai alasan penolakan.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
