<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kuota penerimaan per jenjang untuk tahun ajaran aktif. `quota` kosong
     * berarti tanpa batas; `close_when_full` menutup formulir otomatis begitu
     * pendaftar (selain yang Ditolak) mencapai kuota.
     */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->unsignedInteger('quota')->nullable()->after('form_enabled');
            $table->boolean('close_when_full')->default(false)->after('quota');
            $table->text('quota_full_message')->nullable()->after('closed_message');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['quota', 'close_when_full', 'quota_full_message']);
        });
    }
};
