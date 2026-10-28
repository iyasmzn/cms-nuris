<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bayar tunai langsung ke kantor sebagai pilihan di samping transfer.
     * Saklarnya nullable seperti saklar pembayaran lain — `null` berarti ikut
     * Pengaturan PPDB global — dan petunjuknya (alamat kantor, jam layanan)
     * kosong berarti memakai petunjuk global.
     */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->boolean('payment_cash_enabled')->nullable()->after('payment_deadline_hours');
            $table->text('payment_cash_instructions')->nullable()->after('payment_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['payment_cash_enabled', 'payment_cash_instructions']);
        });
    }
};
