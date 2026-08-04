<?php

use App\Models\Institution;
use App\Models\PpdbField;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * `full_name` joined the locked keys after the previous backfill ran, so
     * repeat the reconciliation: every jenjang with a dynamic form gets the
     * locked fields, and the ones it already has are forced back to required
     * and active.
     */
    public function up(): void
    {
        Institution::query()
            ->whereHas('ppdbFields')
            ->get()
            ->each(fn (Institution $institution) => $institution->ensureLockedPpdbFields());

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
