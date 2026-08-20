<?php

namespace App\Filament\Resources\SpmbRegistrations\Tables;

use App\Filament\Actions\UpdateRegistrationStatusAction;
use App\Filament\Resources\SpmbRegistrations\SpmbRegistrationResource;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\PpdbField;
use App\Models\RegistrationPayment;
use App\Models\RegistrationWave;
use App\Models\SpmbRegistration;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SpmbRegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_number')
                    ->label('No. Daftar')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->description(fn (SpmbRegistration $record): ?string => $record->previous_school),

                TextColumn::make('institution.short_name')
                    ->label('Jenjang')
                    ->state(fn (SpmbRegistration $record): ?string => $record->institution?->short_name ?? $record->institution?->name)
                    ->badge()
                    ->color(fn (SpmbRegistration $record): string => $record->institution?->color ?? 'gray')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('admissionPath.name')
                    ->label('Jalur')
                    ->badge()
                    ->color(fn (SpmbRegistration $record): string => $record->admissionPath?->color ?? 'gray')
                    ->formatStateUsing(fn (?string $state, SpmbRegistration $record): string => trim(($record->admissionPath?->icon ?? '').' '.($state ?? '—')))
                    ->placeholder('—'),

                TextColumn::make('academicYear.label')
                    ->label('Tahun Ajaran')
                    ->state(fn (SpmbRegistration $record): ?string => $record->academicYear?->label)
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('registrationWave.name')
                    ->label('Gelombang')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('status')
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

                TextColumn::make('payment.status')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        RegistrationPayment::STATUS_UNPAID => 'warning',
                        RegistrationPayment::STATUS_WAITING => 'info',
                        RegistrationPayment::STATUS_PAID => 'success',
                        RegistrationPayment::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => RegistrationPayment::statusOptions()[$state] ?? $state)
                    ->placeholder('Tanpa tagihan')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Tgl. Daftar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('jenjang')
                    ->label('Jenjang & Data Formulir')
                    ->schema([
                        Select::make('institution_id')
                            ->label('Jenjang')
                            ->options(fn (): array => Institution::query()
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                // Pilihan field milik jenjang sebelumnya, jadi
                                // tidak boleh ikut terbawa ke jenjang baru.
                                foreach (self::choiceFields() as $field) {
                                    $set(self::choiceFieldKey($field), null);
                                }
                            }),

                        Grid::make(1)
                            ->schema(fn (Get $get): array => blank($get('institution_id'))
                                ? []
                                : self::choiceFields($get('institution_id'))
                                    ->map(fn (PpdbField $field): Select => Select::make(self::choiceFieldKey($field))
                                        ->label($field->label)
                                        ->options(array_combine($field->optionValues(), $field->optionValues()))
                                        ->native(false)
                                        ->placeholder('Semua'))
                                    ->all()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $institutionId = $data['institution_id'] ?? null;

                        if (blank($institutionId)) {
                            return $query;
                        }

                        $query->where('institution_id', $institutionId);

                        foreach (self::choiceFields($institutionId) as $field) {
                            $value = $data[self::choiceFieldKey($field)] ?? null;

                            if (blank($value)) {
                                continue;
                            }

                            in_array($field->key, SpmbRegistration::dynamicColumnKeys(), true)
                                ? $query->where($field->key, $value)
                                : $query->where("data->{$field->key}", $value);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $institution = Institution::find($data['institution_id'] ?? null);

                        if ($institution === null) {
                            return [];
                        }

                        $indicators = [
                            Indicator::make('Jenjang: '.($institution->short_name ?: $institution->name))
                                ->removeField('institution_id'),
                        ];

                        foreach (self::choiceFields($institution->id) as $field) {
                            $value = $data[self::choiceFieldKey($field)] ?? null;

                            if (blank($value)) {
                                continue;
                            }

                            $indicators[] = Indicator::make("{$field->label}: {$value}")
                                ->removeField(self::choiceFieldKey($field));
                        }

                        return $indicators;
                    }),

                Filter::make('periode')
                    ->label('Tahun Ajaran & Gelombang')
                    ->schema([
                        Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->options(fn (): array => AcademicYear::query()
                                ->orderByDesc('is_active')
                                ->orderByDesc('year_start')
                                ->get()
                                ->mapWithKeys(fn (AcademicYear $year): array => [
                                    $year->id => $year->label.($year->is_active ? ' (Aktif)' : ''),
                                ])
                                ->all())
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('registration_wave_id', null)),

                        Select::make('registration_wave_id')
                            ->label('Gelombang')
                            ->options(fn (Get $get): array => blank($get('academic_year_id'))
                                ? []
                                : RegistrationWave::query()
                                    ->where('academic_year_id', $get('academic_year_id'))
                                    ->orderBy('start_date')
                                    ->pluck('name', 'id')
                                    ->all())
                            ->native(false)
                            ->disabled(fn (Get $get): bool => blank($get('academic_year_id')))
                            ->placeholder('Pilih tahun ajaran dahulu'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['academic_year_id'] ?? null, fn (Builder $q, $id) => $q->where('academic_year_id', $id))
                        ->when($data['registration_wave_id'] ?? null, fn (Builder $q, $id) => $q->where('registration_wave_id', $id)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($year = AcademicYear::find($data['academic_year_id'] ?? null)) {
                            $indicators[] = Indicator::make("T.A. {$year->label}")->removeField('academic_year_id');
                        }

                        if ($wave = RegistrationWave::find($data['registration_wave_id'] ?? null)) {
                            $indicators[] = Indicator::make("Gelombang: {$wave->name}")->removeField('registration_wave_id');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('admission_path_id')
                    ->label('Jalur')
                    ->relationship('admissionPath', 'name')
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(SpmbRegistration::statusOptions())
                    ->native(false),

                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options(RegistrationPayment::statusOptions())
                    ->native(false)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $status): Builder => $q->whereHas(
                            'payment',
                            fn (Builder $payment): Builder => $payment->where('status', $status),
                        ),
                    )),
            ])
            ->recordUrl(fn (SpmbRegistration $record): string => SpmbRegistrationResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Lihat'),
                UpdateRegistrationStatusAction::make()->label('Status')->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('terbitkanTagihan')
                        ->label('Terbitkan Tagihan')
                        ->icon(Heroicon::OutlinedCreditCard)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Terbitkan Tagihan Pendaftaran')
                        ->modalDescription('Untuk pendaftar yang masuk sebelum nominal biaya diatur. Pendaftar yang sudah punya tagihan, atau yang jenjangnya belum punya nominal, dilewati.')
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => setting_bool('spmb_payment_enabled', false)
                            && (auth()->user()?->can('Create:RegistrationPayment') ?? false))
                        ->action(function (Collection $records): void {
                            $issued = $records
                                ->map(fn (SpmbRegistration $record): ?RegistrationPayment => RegistrationPayment::issueFor($record))
                                ->filter()
                                ->count();

                            $skipped = $records->count() - $issued;

                            Notification::make()
                                ->success()
                                ->title("{$issued} tagihan diterbitkan")
                                ->body($skipped > 0 ? "{$skipped} pendaftar dilewati (sudah punya tagihan atau jenjangnya tanpa nominal biaya)." : null)
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * The active option-style (dropdown/radio) fields of a jenjang's dynamic
     * formulir — the ones whose answers come from a fixed list and are
     * therefore worth filtering on. Pass no jenjang to get every one of them,
     * which is how the filter clears selections that belonged to the jenjang
     * the admin just switched away from.
     *
     * @return Collection<int, PpdbField>
     */
    private static function choiceFields(int|string|null $institutionId = null): Collection
    {
        return PpdbField::query()
            ->active()
            ->whereIn('type', ['select', 'radio'])
            ->when($institutionId, fn (Builder $query, $id): Builder => $query->where('institution_id', $id))
            ->ordered()
            ->get();
    }

    /**
     * The filter form field name carrying a dynamic field's selected value.
     * Keyed by id, not by key: two jenjang may use the same key for different
     * option lists.
     */
    private static function choiceFieldKey(PpdbField $field): string
    {
        return "field_{$field->id}";
    }
}
