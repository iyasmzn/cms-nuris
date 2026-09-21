<?php

namespace App\Models;

use Database\Factories\SpmbRegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SpmbRegistration extends Model
{
    /** @use HasFactory<SpmbRegistrationFactory> */
    use HasFactory;

    /**
     * The keterangan a pendaftar sees right after submitting, used when
     * neither the jenjang nor the global Setting defines one.
     */
    public const DEFAULT_SUCCESS_MESSAGE = 'Pendaftaran berhasil dikirim dengan nomor {nomor_pendaftaran}! Kami akan segera menghubungi Anda untuk proses verifikasi.';

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'registration_wave_id',
        'admission_path_id',
        'full_name',
        'nik',
        'email',
        'phone',
        'birth_date',
        'birth_place',
        'previous_school',
        'previous_school_city',
        'address',
        'parent_name',
        'parent_phone',
        'notes',
        'data',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'verified_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Registration columns a dynamic form field may write to directly, matched
     * by the field's key. Any other field key is stored in the `data` JSON
     * bucket instead.
     *
     * @return array<int, string>
     */
    public static function dynamicColumnKeys(): array
    {
        return [
            'full_name', 'nik', 'email', 'phone', 'birth_date', 'birth_place',
            'previous_school', 'previous_school_city', 'address', 'parent_name',
            'parent_phone', 'notes',
        ];
    }

    /**
     * Digits-only form of a nomor HP with the Indonesian country code folded
     * back to a leading zero, so `0812…`, `62812…` and `+62 812-3456-…` all
     * compare equal when looking for a double submission.
     */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Comparable form of a nama lengkap: lowercased with every space removed,
     * so casing and stray spacing never let the same person through twice.
     */
    public static function normalizeName(?string $name): string
    {
        return Str::lower(preg_replace('/\s+/', '', trim((string) $name)) ?? '');
    }

    /**
     * An existing registration in the same intake made by the same pendaftar,
     * matched on nama lengkap + nomor HP. Not every jenjang collects a NIK, so
     * this is the guard that catches a double submission (a double-clicked
     * form, or a pendaftar filling the form twice) on those forms too.
     */
    public static function duplicateIn(int $institutionId, ?int $academicYearId, ?string $fullName, ?string $phone): ?self
    {
        $name = self::normalizeName($fullName);
        $number = self::normalizePhone($phone);

        if ($name === '' || $number === '') {
            return null;
        }

        return self::query()
            ->where('institution_id', $institutionId)
            ->when(
                $academicYearId === null,
                fn (Builder $query): Builder => $query->whereNull('academic_year_id'),
                fn (Builder $query): Builder => $query->where('academic_year_id', $academicYearId),
            )
            ->whereRaw("LOWER(REPLACE(full_name, ' ', '')) = ?", [$name])
            ->limit(20)
            ->get()
            ->first(fn (self $candidate): bool => self::normalizePhone($candidate->phone) === $number);
    }

    protected static function booted(): void
    {
        static::created(function (self $registration): void {
            if (filled($registration->registration_number)) {
                return;
            }

            $short = Str::upper($registration->institution?->short_name ?: 'REG');
            $year = $registration->academicYear?->year_start ?: now()->year;

            $registration->registration_number = sprintf('%s-%s-%04d', $short, $year, $registration->id);
            $registration->saveQuietly();
        });
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<RegistrationWave, $this> */
    public function registrationWave(): BelongsTo
    {
        return $this->belongsTo(RegistrationWave::class);
    }

    /** @return BelongsTo<AdmissionPath, $this> */
    public function admissionPath(): BelongsTo
    {
        return $this->belongsTo(AdmissionPath::class);
    }

    /**
     * The tagihan biaya pendaftaran, present only when the jenjang charges a
     * fee and payment handling is switched on.
     *
     * @return HasOne<RegistrationPayment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(RegistrationPayment::class);
    }

    /**
     * Values an admin may drop into a keterangan template, keyed by the
     * placeholder that stands for them.
     *
     * @return array<string, string>
     */
    public function messagePlaceholders(): array
    {
        return [
            '{nomor_pendaftaran}' => (string) $this->registration_number,
            '{nama}' => (string) $this->full_name,
            '{jenjang}' => (string) ($this->institution?->short_name ?: $this->institution?->name),
            '{tahun_ajaran}' => (string) $this->academicYear?->label,
        ];
    }

    /**
     * The jenjang's keterangan setelah pendaftaran with its placeholders
     * filled in. Each jenjang may word this differently; an empty one falls
     * back to the global Setting and then to `DEFAULT_SUCCESS_MESSAGE`.
     */
    public function successMessage(): string
    {
        $template = $this->institution?->resolvedSuccessMessage() ?: self::DEFAULT_SUCCESS_MESSAGE;
        $placeholders = $this->messagePlaceholders();

        return trim(str_replace(array_keys($placeholders), array_values($placeholders), $template));
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            'pending' => 'Menunggu',
            'verified' => 'Terverifikasi',
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
        ];
    }

    /**
     * Whether the public registration form should accept new submissions:
     * a wave of the active academic year must currently be open, and the
     * admin must not have force-closed the form. Pass an institution to check
     * a single jenjang (SD/SMP/SMA).
     */
    public static function isOpen(?Institution $institution = null): bool
    {
        if (! (bool) Setting::get('spmb_form_enabled', true)) {
            return false;
        }

        return RegistrationWave::currentOpen($institution) !== null;
    }

    /**
     * Narrow a query to the jenjang a panel user is allowed to see. Panitia
     * unit SD hanya melihat pendaftar SD; akun tanpa unit sama sekali tidak
     * melihat apa pun, dan pemegang `ViewAll:SpmbRegistration` melihat semua.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        $visible = $user->visibleInstitutionIds();

        return $visible === null ? $query : $query->whereIn('institution_id', $visible);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }
}
