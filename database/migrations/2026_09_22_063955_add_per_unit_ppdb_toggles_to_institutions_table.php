<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saklar Pengaturan PPDB yang kini bisa ditimpa per jenjang: buka/tutup
     * formulir, penagihan biaya pendaftaran, kode unik, dan batas waktu bayar.
     *
     * Semuanya nullable dengan sengaja — `null` berarti "ikut pengaturan
     * global", bukan "mati". Itulah yang membedakannya dari boolean biasa.
     */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->boolean('form_enabled')->nullable()->after('form_mode');
            $table->boolean('payment_enabled')->nullable()->after('registration_fee');
            $table->boolean('payment_unique_code')->nullable()->after('payment_enabled');
            $table->unsignedSmallInteger('payment_deadline_hours')->nullable()->after('payment_unique_code');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['form_enabled', 'payment_enabled', 'payment_unique_code', 'payment_deadline_hours']);
        });
    }
};
