<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekening tujuan, instruksi pembayaran dan keterangan setelah pendaftaran
     * differ per jenjang: setiap unit punya rekeningnya sendiri dan kalimat
     * penutup sendiri. Semuanya nullable — kosong berarti tetap memakai
     * pengaturan global di Pengaturan PPDB.
     */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->json('bank_accounts')->nullable()->after('registration_fee');
            $table->text('payment_instructions')->nullable()->after('bank_accounts');
            $table->text('success_message')->nullable()->after('closed_message');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['bank_accounts', 'payment_instructions', 'success_message']);
        });
    }
};
