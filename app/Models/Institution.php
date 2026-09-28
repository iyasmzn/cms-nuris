<?php

namespace App\Models;

use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    public const FORM_MODE_INTERNAL = 'internal';

    public const FORM_MODE_EXTERNAL_LINK = 'external_link';

    public const FORM_MODE_EMBED = 'embed';

    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'icon',
        'icon_image',
        'color',
        'description',
        'detail_url',
        'address',
        'sort_order',
        'is_active',
        'form_mode',
        'form_enabled',
        'external_url',
        'embed_url',
        'procedures',
        'fees',
        'requirements',
        'registration_fee',
        'payment_enabled',
        'payment_unique_code',
        'payment_deadline_hours',
        'bank_accounts',
        'payment_instructions',
        'form_title',
        'form_description',
        'closed_message',
        'success_message',
        'show_status_button',
        'show_requirements',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'show_status_button' => 'boolean',
        'show_requirements' => 'boolean',
        'procedures' => 'array',
        'fees' => 'array',
        'requirements' => 'array',
        'bank_accounts' => 'array',
        'registration_fee' => 'integer',
        // Ketiganya sengaja nullable: null = ikut pengaturan global.
        'form_enabled' => 'boolean',
        'payment_enabled' => 'boolean',
        'payment_unique_code' => 'boolean',
        'payment_deadline_hours' => 'integer',
    ];

    /** @return HasMany<Teacher, $this> */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    /** @return HasMany<RegistrationWave, $this> */
    public function waves(): HasMany
    {
        return $this->hasMany(RegistrationWave::class);
    }

    /** @return HasMany<SpmbRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(SpmbRegistration::class);
    }

    /** @return BelongsToMany<AdmissionPath, $this> */
    public function admissionPaths(): BelongsToMany
    {
        return $this->belongsToMany(AdmissionPath::class);
    }

    /** @return HasMany<PpdbField, $this> */
    public function ppdbFields(): HasMany
    {
        return $this->hasMany(PpdbField::class);
    }

    /**
     * Add the fields this jenjang is not allowed to do without (see
     * `PpdbField::lockedKeys()`), leaving any it already has untouched. Called
     * whenever a dynamic field is added, so the set can never end up without
     * the nomor HP the status lookup depends on.
     */
    public function ensureLockedPpdbFields(): void
    {
        foreach (PpdbField::lockedFieldDefaults() as $defaults) {
            if ($this->ppdbFields()->where('key', $defaults['key'])->exists()) {
                continue;
            }

            $this->ppdbFields()->create($defaults + [
                'is_required' => true,
                'is_active' => true,
                'sort_order' => ((int) $this->ppdbFields()->max('sort_order')) + 1,
            ]);
        }
    }

    /** @return array<string, string> */
    public static function formModeOptions(): array
    {
        return [
            self::FORM_MODE_INTERNAL => 'Formulir internal (data tersimpan di sistem)',
            self::FORM_MODE_EXTERNAL_LINK => 'Tautan eksternal (buka situs lain)',
            self::FORM_MODE_EMBED => 'Sematkan formulir situs lain (embed)',
        ];
    }

    /**
     * Whether registrations for this jenjang are collected and stored in this
     * system via the built-in form.
     */
    public function usesInternalForm(): bool
    {
        return $this->form_mode === self::FORM_MODE_INTERNAL;
    }

    /**
     * Whether registrations are handled by an external site linked via a button.
     */
    public function usesExternalLink(): bool
    {
        return $this->form_mode === self::FORM_MODE_EXTERNAL_LINK;
    }

    /**
     * Whether registrations are handled by an embedded (iframe) external form.
     */
    public function usesEmbed(): bool
    {
        return $this->form_mode === self::FORM_MODE_EMBED;
    }

    /**
     * Whether this jenjang is currently accepting registrations, taking the
     * global on/off switch and the form mode (open wave / external / embed)
     * into account. Drives the open/closed status shown on the public site.
     */
    public function registrationOpen(): bool
    {
        if (! $this->formEnabled()) {
            return false;
        }

        return match ($this->form_mode) {
            self::FORM_MODE_EXTERNAL_LINK => filled($this->external_url),
            self::FORM_MODE_EMBED => filled($this->embed_url),
            default => RegistrationWave::currentOpen($this) !== null,
        };
    }

    /**
     * Registration procedures for this jenjang, falling back to the global
     * Setting when this jenjang has none of its own.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolvedProcedures(): array
    {
        return $this->procedures ?: (json_decode((string) Setting::get('spmb_procedures', ''), true) ?: []);
    }

    /**
     * Registration fees for this jenjang, falling back to the global Setting.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolvedFees(): array
    {
        return $this->fees ?: (json_decode((string) Setting::get('spmb_fees', ''), true) ?: []);
    }

    /**
     * Document requirements shown on this jenjang's PPDB page, falling back to
     * the global Setting. Returns an empty list when this jenjang hides them,
     * so a global list can still be switched off for a single jenjang.
     *
     * @return array<int, string>
     */
    public function resolvedRequirements(): array
    {
        if (! $this->showsRequirements()) {
            return [];
        }

        $requirements = $this->requirements ?: (json_decode((string) Setting::get('spmb_requirements', ''), true) ?: []);

        return array_values(array_filter(
            array_map(static fn ($requirement): string => trim((string) $requirement), $requirements),
            static fn (string $requirement): bool => $requirement !== '',
        ));
    }

    /**
     * Whether the "Persyaratan Dokumen" card appears on this jenjang's public
     * page at all — some jenjang mengurus berkasnya di luar sistem ini.
     */
    public function showsRequirements(): bool
    {
        return (bool) ($this->show_requirements ?? true);
    }

    /**
     * Rekening tujuan transfer for this jenjang, falling back to the global
     * Setting when this jenjang has none of its own. Half-filled rows are
     * dropped, so an empty list means "no rekening configured anywhere".
     *
     * @return array<int, array{bank: string, number: string, holder: string}>
     */
    public function resolvedBankAccounts(): array
    {
        $accounts = spmb_bank_accounts($this->bank_accounts ?: null);

        return $accounts !== [] ? $accounts : spmb_bank_accounts();
    }

    /**
     * Payment instructions shown above the bukti transfer form, falling back
     * to the global Setting.
     */
    public function resolvedPaymentInstructions(): string
    {
        return $this->payment_instructions ?: (string) Setting::get('spmb_payment_instructions', '');
    }

    /**
     * The keterangan shown to a pendaftar right after they submit the form,
     * as a template still holding its placeholders. Falls back to the global
     * Setting, then to a sensible default. See
     * `SpmbRegistration::successMessage()` for the filled-in version.
     */
    public function resolvedSuccessMessage(): string
    {
        return $this->success_message
            ?: (string) Setting::get('spmb_success_message', SpmbRegistration::DEFAULT_SUCCESS_MESSAGE);
    }

    /**
     * Whether this jenjang's formulir accepts submissions at all. Falls back
     * to the global switch when the jenjang has no opinion of its own, so satu
     * jenjang bisa ditutup tanpa menutup jenjang lain.
     */
    public function formEnabled(): bool
    {
        return $this->form_enabled ?? setting_bool('spmb_form_enabled', true);
    }

    /**
     * Whether biaya pendaftaran is collected for this jenjang, falling back to
     * the global switch.
     */
    public function paymentEnabled(): bool
    {
        return $this->payment_enabled ?? setting_bool('spmb_payment_enabled', false);
    }

    /**
     * Whether a 3-digit kode unik is added to this jenjang's tagihan so two
     * transfers of the same nominal stay distinguishable in the mutasi.
     */
    public function usesUniqueCode(): bool
    {
        return $this->payment_unique_code ?? setting_bool('spmb_payment_unique_code', true);
    }

    /**
     * How long a pendaftar has to settle a tagihan of this jenjang, in hours.
     * Zero means no deadline at all.
     */
    public function paymentDeadlineHours(): int
    {
        return $this->payment_deadline_hours ?? (int) Setting::get('spmb_payment_deadline_hours', 48);
    }

    /**
     * Whether payment handling is in play anywhere — globally, or switched on
     * by at least one jenjang. Drives panel elements that are not tied to a
     * single jenjang, such as the dashboard chart and table-wide actions.
     */
    public static function paymentEnabledAnywhere(): bool
    {
        return setting_bool('spmb_payment_enabled', false)
            || static::query()->where('payment_enabled', true)->exists();
    }

    /**
     * Whether a tagihan biaya pendaftaran should be issued for this jenjang:
     * payment handling must be switched on globally and the jenjang must have
     * a fee above zero.
     */
    public function chargesRegistrationFee(): bool
    {
        return $this->paymentEnabled()
            && (int) ($this->registration_fee ?? 0) > 0;
    }

    public function resolvedFormTitle(): string
    {
        return $this->form_title ?: (string) Setting::get('spmb_form_title', 'Formulir Pendaftaran SPMB');
    }

    public function resolvedFormDescription(): string
    {
        return $this->form_description ?: (string) Setting::get('spmb_form_description', 'Isi formulir di bawah ini dengan data yang benar dan lengkap.');
    }

    /**
     * Whether the "Cek Status & Pembayaran" entry points are shown on this
     * jenjang's public page. Jenjang yang mendaftar lewat situs lain biasanya
     * mengurus status dan pembayarannya di luar sistem ini.
     */
    public function showsStatusButton(): bool
    {
        return (bool) ($this->show_status_button ?? true);
    }

    public function resolvedClosedMessage(): string
    {
        return $this->closed_message ?: (string) Setting::get('spmb_closed_message', 'Pendaftaran SPMB saat ini sedang ditutup.');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
