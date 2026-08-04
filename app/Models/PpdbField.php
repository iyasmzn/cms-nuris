<?php

namespace App\Models;

use Database\Factories\PpdbFieldFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpdbField extends Model
{
    /** @use HasFactory<PpdbFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'key',
        'label',
        'type',
        'options',
        'placeholder',
        'help_text',
        'is_required',
        'width',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Field keys every jenjang must keep collecting. A pendaftar gets back to
     * their status page with nomor pendaftaran + nomor HP, so a formulir
     * without a phone number locks them out; a pendaftaran without a nama is
     * unusable to the panitia. Both used to crash on submission when an admin
     * left them out. An admin may still retitle these fields, just not rename
     * the key, change the type, make them optional, deactivate or delete them.
     *
     * @return array<int, string>
     */
    public static function lockedKeys(): array
    {
        return ['full_name', 'phone'];
    }

    /**
     * The locked fields as they are created for a jenjang that lacks them.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function lockedFieldDefaults(): array
    {
        return [
            ['key' => 'full_name', 'label' => 'Nama Lengkap', 'type' => 'text', 'width' => 'full', 'placeholder' => 'Sesuai akta kelahiran'],
            ['key' => 'phone', 'label' => 'No. HP / WhatsApp', 'type' => 'tel', 'width' => 'half', 'placeholder' => '08xxxxxxxxxx', 'help_text' => 'Dipakai untuk cek status pendaftaran.'],
        ];
    }

    public function isLocked(): bool
    {
        return in_array($this->key, self::lockedKeys(), true);
    }

    /**
     * The parts of a locked field an admin is free to shape.
     *
     * @return array<int, string>
     */
    private static function editableLockedAttributes(): array
    {
        return ['label', 'placeholder', 'help_text', 'width', 'sort_order'];
    }

    protected static function booted(): void
    {
        // A jenjang keeps exactly one row per locked key. When something tries
        // to add a second — a seeder listing `phone` itself, right after the
        // hook below auto-added it — fold the definition into the existing row
        // instead of hitting the unique index.
        static::creating(function (self $field): bool {
            if (! $field->isLocked()) {
                return true;
            }

            $existing = static::query()
                ->where('institution_id', $field->institution_id)
                ->where('key', $field->key)
                ->first();

            if ($existing === null) {
                return true;
            }

            $existing->fill(array_intersect_key(
                $field->getAttributes(),
                array_flip(self::editableLockedAttributes()),
            ))->save();

            return false;
        });

        static::saving(function (self $field): void {
            // Restore whatever the admin tried to change on a locked field.
            // The UI disables these inputs; this closes every other path.
            if (in_array($field->getOriginal('key'), self::lockedKeys(), true)) {
                $field->key = $field->getOriginal('key');
                $field->type = $field->getOriginal('type');
            }

            if ($field->isLocked()) {
                $field->is_required = true;
                $field->is_active = true;
            }
        });

        static::deleting(fn (self $field): bool => ! $field->isLocked());

        // A jenjang either uses the classic fixed form (no fields at all, which
        // asks for both anyway) or a dynamic one — and the moment it has its
        // first dynamic field, the locked fields have to be in the set. Safe to
        // run for a locked field too: the pass that follows finds them present.
        static::created(fn (self $field) => $field->institution?->ensureLockedPpdbFields());
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return [
            'text' => 'Teks singkat',
            'textarea' => 'Teks panjang',
            'number' => 'Angka',
            'email' => 'Email',
            'tel' => 'Nomor telepon',
            'date' => 'Tanggal',
            'select' => 'Dropdown pilihan',
            'radio' => 'Pilihan (radio)',
            'file' => 'Unggah berkas',
        ];
    }

    /**
     * Field types that carry a fixed set of options.
     */
    public function hasChoices(): bool
    {
        return in_array($this->type, ['select', 'radio'], true);
    }

    /**
     * Flatten the stored options into a list of string values. Options are
     * stored as an array of `['value' => '...']` rows from the admin repeater.
     *
     * @return array<int, string>
     */
    public function optionValues(): array
    {
        return collect($this->options ?? [])
            ->map(fn ($option): string => is_array($option) ? (string) ($option['value'] ?? '') : (string) $option)
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
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
