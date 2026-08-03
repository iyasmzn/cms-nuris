<?php

namespace App\Models;

use Database\Factories\RegistrationPaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RegistrationPayment extends Model
{
    /** @use HasFactory<RegistrationPaymentFactory> */
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_WAITING = 'waiting_verification';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const METHOD_MANUAL_TRANSFER = 'manual_transfer';

    public const METHOD_GATEWAY = 'gateway';

    protected $fillable = [
        'spmb_registration_id',
        'invoice_number',
        'amount',
        'unique_code',
        'method',
        'status',
        'bank_account',
        'sender_name',
        'transferred_on',
        'proof_path',
        'submitted_at',
        'verified_at',
        'verified_by',
        'note',
        'expires_at',
        'provider',
        'reference',
        'payload',
    ];

    protected $casts = [
        'amount' => 'integer',
        'unique_code' => 'integer',
        'transferred_on' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (self $payment): void {
            if (filled($payment->invoice_number)) {
                return;
            }

            $registration = $payment->registration;
            $short = Str::upper($registration?->institution?->short_name ?: 'REG');
            $year = $registration?->academicYear?->year_start ?: now()->year;

            $payment->invoice_number = sprintf('INV-%s-%s-%04d', $short, $year, $payment->id);
            $payment->saveQuietly();
        });
    }

    /** @return BelongsTo<SpmbRegistration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(SpmbRegistration::class, 'spmb_registration_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_UNPAID => 'Belum dibayar',
            self::STATUS_WAITING => 'Menunggu verifikasi',
            self::STATUS_PAID => 'Lunas',
            self::STATUS_REJECTED => 'Bukti ditolak',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    /**
     * The exact amount the pendaftar must transfer: the fee plus the 3-digit
     * kode unik that makes an incoming transfer identifiable in the mutasi.
     */
    public function total(): int
    {
        return $this->amount + $this->unique_code;
    }

    /**
     * Whether the pendaftar may still submit (or resubmit) a bukti transfer.
     * A rejected bukti is intentionally allowed a second attempt.
     */
    public function acceptsProof(): bool
    {
        return in_array($this->status, [self::STATUS_UNPAID, self::STATUS_REJECTED], true)
            && ! $this->isExpired();
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Past its payment deadline while still unsettled. Derived from
     * `expires_at` so no scheduled job is needed to keep the status honest.
     * A bukti already awaiting verification is never treated as expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->expires_at === null || $this->isSettled() || $this->status === self::STATUS_WAITING) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Record a bukti transfer uploaded by the pendaftar and queue it for the
     * panitia. Clears any previous rejection note so the panel only ever shows
     * the current attempt.
     *
     * @param  array{bank_account?: ?string, sender_name?: ?string, transferred_on?: ?string, proof_path: string}  $attributes
     */
    public function submitProof(array $attributes): void
    {
        $this->forceFill([
            'bank_account' => $attributes['bank_account'] ?? null,
            'sender_name' => $attributes['sender_name'] ?? null,
            'transferred_on' => $attributes['transferred_on'] ?? null,
            'proof_path' => $attributes['proof_path'],
            'status' => self::STATUS_WAITING,
            'submitted_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
            'note' => null,
        ])->save();
    }

    /**
     * Mark the tagihan settled. Also nudges the registration itself out of
     * `pending` so the panitia list reflects a paid, ready-to-process entry.
     */
    public function markPaid(?User $verifier = null, ?string $note = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_PAID,
            'verified_at' => now(),
            'verified_by' => $verifier?->id,
            'note' => $note,
        ])->save();

        $registration = $this->registration;

        if ($registration !== null && $registration->status === 'pending') {
            $registration->forceFill([
                'status' => 'verified',
                'verified_at' => $registration->verified_at ?? now(),
            ])->save();
        }
    }

    public function markRejected(?User $verifier = null, ?string $reason = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'verified_at' => now(),
            'verified_by' => $verifier?->id,
            'note' => $reason,
        ])->save();
    }

    /**
     * Pick a 3-digit kode unik that no other outstanding tagihan of the same
     * base amount is using, so two transfers of the same nominal can still be
     * told apart in the rekening mutasi. Returns 0 when the feature is off or
     * when every code is already taken.
     */
    public static function allocateUniqueCode(int $amount): int
    {
        if (! setting_bool('spmb_payment_unique_code', true)) {
            return 0;
        }

        $taken = static::query()
            ->where('amount', $amount)
            ->whereIn('status', [self::STATUS_UNPAID, self::STATUS_WAITING, self::STATUS_REJECTED])
            ->pluck('unique_code')
            ->all();

        $available = array_values(array_diff(range(1, 999), $taken));

        return $available === [] ? 0 : $available[array_rand($available)];
    }

    /**
     * Issue a tagihan for a registration using its jenjang's fee. Returns null
     * when payment handling is switched off, the jenjang charges nothing, or a
     * tagihan already exists — the last guard keeps this safe to call again on
     * registrations submitted before the fee was configured.
     */
    public static function issueFor(SpmbRegistration $registration): ?self
    {
        if (! setting_bool('spmb_payment_enabled', false)) {
            return null;
        }

        if ($registration->payment()->exists()) {
            return null;
        }

        $amount = (int) ($registration->institution?->registration_fee ?? 0);

        if ($amount <= 0) {
            return null;
        }

        $deadlineHours = (int) setting('spmb_payment_deadline_hours', 48);

        return static::create([
            'spmb_registration_id' => $registration->id,
            'amount' => $amount,
            'unique_code' => static::allocateUniqueCode($amount),
            'method' => self::METHOD_MANUAL_TRANSFER,
            'status' => self::STATUS_UNPAID,
            'expires_at' => $deadlineHours > 0 ? Carbon::now()->addHours($deadlineHours) : null,
        ]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWaitingVerification(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }
}
