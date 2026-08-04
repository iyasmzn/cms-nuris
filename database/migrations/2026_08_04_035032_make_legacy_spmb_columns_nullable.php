<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `phone` and `previous_school` date from the fixed registration form. Now
     * that each jenjang builds its own form through `ppdb_fields`, an admin can
     * mark either field optional or leave it out entirely — a TK has no sekolah
     * asal — so these NOT NULL constraints only turn valid submissions into 500s.
     */
    public function up(): void
    {
        Schema::table('spmb_registrations', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
            $table->string('previous_school')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('spmb_registrations')->whereNull('phone')->update(['phone' => '']);
        DB::table('spmb_registrations')->whereNull('previous_school')->update(['previous_school' => '']);

        Schema::table('spmb_registrations', function (Blueprint $table) {
            $table->string('phone', 20)->nullable(false)->change();
            $table->string('previous_school')->nullable(false)->change();
        });
    }
};
