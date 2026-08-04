<?php

use App\Models\Institution;
use App\Models\PpdbField;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Bring existing jenjang in line with the locked-field rule: a formulir
     * that already has dynamic fields must collect a nomor HP, because that is
     * half of the credential for the status page. Jenjang with no fields at all
     * still use the classic form, which asks for it anyway.
     */
    public function up(): void
    {
        Institution::query()
            ->whereHas('ppdbFields')
            ->get()
            ->each(fn (Institution $institution) => $institution->ensureLockedPpdbFields());

        // Saving through the model re-applies the required/active guard.
        PpdbField::query()
            ->whereIn('key', PpdbField::lockedKeys())
            ->get()
            ->each(fn (PpdbField $field) => $field->save());
    }

    /**
     * Nothing to reverse: the fields are legitimate registration fields, and
     * dropping them would throw away an admin's labels.
     */
    public function down(): void {}
};
