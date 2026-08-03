<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The registration fee actually billed per jenjang, in whole rupiah. The
     * existing `fees` repeater stays informational (SPP, seragam, dll); this
     * single number is what a tagihan pendaftaran is issued for. Null or 0
     * means the jenjang charges nothing at registration.
     */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->unsignedInteger('registration_fee')->nullable()->after('fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('registration_fee');
        });
    }
};
