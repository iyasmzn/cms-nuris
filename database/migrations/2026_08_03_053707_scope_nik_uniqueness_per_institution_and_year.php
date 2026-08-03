<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A globally unique NIK locked a santri out of ever registering for a
     * second jenjang: finishing SD and moving up to SMP was rejected as a
     * duplicate. Scope the constraint to one registration per NIK per jenjang
     * per tahun ajaran, which still blocks a genuine double submission into
     * the same intake.
     */
    public function up(): void
    {
        Schema::table('spmb_registrations', function (Blueprint $table) {
            $table->dropUnique('spmb_registrations_nik_unique');
            $table->unique(['nik', 'institution_id', 'academic_year_id'], 'spmb_registrations_nik_intake_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spmb_registrations', function (Blueprint $table) {
            $table->dropUnique('spmb_registrations_nik_intake_unique');
            $table->unique('nik', 'spmb_registrations_nik_unique');
        });
    }
};
