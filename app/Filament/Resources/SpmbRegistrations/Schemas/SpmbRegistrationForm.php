<?php

namespace App\Filament\Resources\SpmbRegistrations\Schemas;

use App\Filament\Resources\RegistrationPayments\RegistrationPaymentResource;
use App\Models\AcademicYear;
use App\Models\AdmissionPath;
use App\Models\PpdbField;
use App\Models\SpmbRegistration;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;

class SpmbRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Pendaftaran')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Select::make('institution_id')
                        ->label('Jenjang')
                        ->relationship('institution', 'name')
                        ->required()
                        ->native(false)
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->relationship('academicYear', 'year_start')
                            ->getOptionLabelFromRecordUsing(fn (AcademicYear $record): string => $record->label)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('registration_wave_id', null)),

                        Select::make('registration_wave_id')
                            ->label('Gelombang')
                            ->relationship(
                                'registrationWave',
                                'name',
                                modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('academic_year_id', $get('academic_year_id')),
                            )
                            ->native(false)
                            ->disabled(fn (Get $get): bool => blank($get('academic_year_id'))),

                        Select::make('admission_path_id')
                            ->label('Jalur Pendaftaran')
                            ->relationship('admissionPath', 'name', fn (Builder $query) => $query->orderBy('sort_order'))
                            ->getOptionLabelFromRecordUsing(fn (AdmissionPath $record): string => trim("{$record->icon} {$record->name}"))
                            ->required()
                            ->native(false),
                    ]),
                ]),

            Section::make('Data Pribadi Calon Peserta')
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->mask('9999999999999999')
                            ->rule('digits:16')
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('institution_id', $get('institution_id'))
                                    ->where('academic_year_id', $get('academic_year_id')),
                            )
                            ->validationMessages([
                                'unique' => 'NIK ini sudah terdaftar pada jenjang dan tahun ajaran yang sama.',
                            ])
                            ->helperText('Nomor Induk Kependudukan, 16 digit. Boleh sama dengan pendaftaran di jenjang atau tahun ajaran lain.'),

                        TextInput::make('phone')
                            ->label('No. HP / WhatsApp')
                            ->tel()
                            ->maxLength(20),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),

                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),

                    TextInput::make('birth_place')
                        ->label('Tempat Lahir')
                        ->maxLength(100),

                    Textarea::make('address')
                        ->label('Alamat Lengkap')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Asal Sekolah')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('previous_school')
                            ->label('Nama Sekolah Asal')
                            ->maxLength(100),

                        TextInput::make('previous_school_city')
                            ->label('Kota / Kabupaten')
                            ->maxLength(100),
                    ]),
                ]),

            Section::make('Data Orang Tua / Wali')
                ->icon('heroicon-o-users')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('parent_name')
                            ->label('Nama Orang Tua / Wali')
                            ->maxLength(100),

                        TextInput::make('parent_phone')
                            ->label('No. HP Orang Tua / Wali')
                            ->tel()
                            ->maxLength(20),
                    ]),
                ]),

            Section::make('Catatan & Status')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label('Status Pendaftaran')
                            ->options(SpmbRegistration::statusOptions())
                            ->required()
                            ->native(false),

                        DatePicker::make('verified_at')
                            ->label('Tanggal Verifikasi')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),
                    ]),

                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Pembayaran')
                ->icon('heroicon-o-credit-card')
                ->visible(fn (?SpmbRegistration $record): bool => $record?->payment !== null)
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('payment_invoice')
                            ->label('Nomor Tagihan')
                            ->content(fn (SpmbRegistration $record): string => $record->payment?->invoice_number ?? '—'),

                        Placeholder::make('payment_total')
                            ->label('Total Ditagihkan')
                            ->content(fn (SpmbRegistration $record): string => rupiah($record->payment?->total())),

                        Placeholder::make('payment_status')
                            ->label('Status')
                            ->content(fn (SpmbRegistration $record): string => $record->payment?->statusLabel() ?? '—'),
                    ]),

                    Placeholder::make('payment_link')
                        ->hiddenLabel()
                        ->content(fn (SpmbRegistration $record): HtmlString => new HtmlString(
                            '<a href="'.e(RegistrationPaymentResource::getUrl('edit', ['record' => $record->payment])).'" class="text-primary-600 underline">💳 Buka detail pembayaran</a>'
                        ))
                        ->columnSpanFull(),
                ]),

            Section::make('Data Tambahan')
                ->description('Nilai field kustom (di luar kolom baku) yang diisi pendaftar melalui formulir dinamis.')
                ->icon('heroicon-o-list-bullet')
                ->collapsed()
                ->schema([
                    KeyValue::make('data')
                        ->hiddenLabel()
                        ->keyLabel('Field')
                        ->valueLabel('Nilai')
                        ->columnSpanFull(),
                ]),

            Section::make('Berkas Terunggah')
                ->icon('heroicon-o-paper-clip')
                ->visible(fn (?SpmbRegistration $record): bool => (bool) $record?->institution?->ppdbFields()->where('type', 'file')->exists())
                ->schema([
                    Placeholder::make('berkas_terunggah')
                        ->hiddenLabel()
                        ->content(function (SpmbRegistration $record): HtmlString {
                            $links = ($record->institution?->ppdbFields()
                                ->where('type', 'file')
                                ->orderBy('sort_order')
                                ->get() ?? collect())
                                ->filter(fn (PpdbField $field): bool => filled(data_get($record->data, $field->key)))
                                ->map(fn (PpdbField $field): string => '<a href="'.e(route('ppdb.berkas', [$record, $field->key])).'" target="_blank" class="text-primary-600 underline">📎 '.e($field->label).'</a>')
                                ->implode('<br>');

                            return new HtmlString($links ?: '<span class="text-gray-500">Belum ada berkas diunggah.</span>');
                        }),
                ]),
        ]);
    }
}
