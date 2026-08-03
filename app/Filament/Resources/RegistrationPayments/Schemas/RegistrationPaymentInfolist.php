<?php

namespace App\Filament\Resources\RegistrationPayments\Schemas;

use App\Filament\Resources\SpmbRegistrations\SpmbRegistrationResource;
use App\Models\RegistrationPayment;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RegistrationPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Tagihan')
                ->icon('heroicon-o-credit-card')
                ->columns(3)
                ->schema([
                    TextEntry::make('invoice_number')
                        ->label('No. Tagihan')
                        ->badge()
                        ->color('gray')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            RegistrationPayment::STATUS_UNPAID => 'warning',
                            RegistrationPayment::STATUS_WAITING => 'info',
                            RegistrationPayment::STATUS_PAID => 'success',
                            RegistrationPayment::STATUS_REJECTED => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => RegistrationPayment::statusOptions()[$state] ?? $state),

                    TextEntry::make('total')
                        ->label('Total Ditagihkan')
                        ->state(fn (RegistrationPayment $record): string => rupiah($record->total()))
                        ->helperText(fn (RegistrationPayment $record): ?string => $record->unique_code > 0
                            ? rupiah($record->amount).' + kode unik '.$record->unique_code
                            : null)
                        ->weight('bold'),

                    TextEntry::make('created_at')
                        ->label('Tgl. Tagihan')
                        ->dateTime('d M Y, H:i'),

                    TextEntry::make('expires_at')
                        ->label('Batas Waktu Bayar')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Tanpa batas')
                        ->color(fn (RegistrationPayment $record): string => $record->isExpired() ? 'danger' : 'gray'),

                    TextEntry::make('method')
                        ->label('Metode')
                        ->formatStateUsing(fn (string $state): string => $state === RegistrationPayment::METHOD_GATEWAY
                            ? 'Payment gateway'
                            : 'Transfer manual'),
                ]),

            Section::make('Pendaftar')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    TextEntry::make('registration.full_name')
                        ->label('Nama')
                        ->weight('bold')
                        ->placeholder('—'),

                    TextEntry::make('registration.registration_number')
                        ->label('No. Pendaftaran')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    TextEntry::make('registration.institution.name')
                        ->label('Jenjang')
                        ->placeholder('—'),

                    TextEntry::make('registration.phone')
                        ->label('No. HP')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('registration.parent_phone')
                        ->label('No. HP Orang Tua')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('registration_link')
                        ->label('Data Lengkap')
                        ->state(fn (RegistrationPayment $record): HtmlString => new HtmlString(
                            $record->registration === null
                                ? '<span class="text-gray-500">—</span>'
                                : '<a href="'.e(SpmbRegistrationResource::getUrl('view', ['record' => $record->registration])).'" class="text-primary-600 underline">👤 Buka data pendaftar</a>'
                        )),
                ]),

            Section::make('Bukti Transfer')
                ->icon('heroicon-o-paper-clip')
                ->columns(3)
                ->schema([
                    TextEntry::make('sender_name')
                        ->label('Nama Pengirim')
                        ->placeholder('Belum ada bukti'),

                    TextEntry::make('transferred_on')
                        ->label('Tgl. Transfer')
                        ->date('d M Y')
                        ->placeholder('—'),

                    TextEntry::make('bank_account')
                        ->label('Rekening Tujuan')
                        ->placeholder('—'),

                    TextEntry::make('submitted_at')
                        ->label('Bukti Dikirim')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('—'),

                    ViewEntry::make('proof')
                        ->label('Berkas')
                        ->view('filament.infolists.payment-proof')
                        ->columnSpanFull(),
                ]),

            Section::make('Verifikasi')
                ->icon('heroicon-o-clipboard-document-check')
                ->columns(2)
                ->schema([
                    TextEntry::make('verified_at')
                        ->label('Waktu Verifikasi')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('—'),

                    TextEntry::make('verifier.name')
                        ->label('Diverifikasi Oleh')
                        ->placeholder('—'),

                    TextEntry::make('note')
                        ->label('Catatan / Alasan Penolakan')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
