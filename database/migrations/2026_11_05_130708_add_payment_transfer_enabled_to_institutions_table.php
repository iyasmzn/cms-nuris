<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saklar transfer bank per jenjang, agar sebuah jenjang bisa menerima
     * pembayaran tunai di kantor saja. Tidak nullable seperti saklar lain
     * karena tidak ada saklar global untuknya — transfer selalu jadi cara
     * bayar bawaan, jadi jenjang yang sudah ada tetap menerima transfer.
     */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->boolean('payment_transfer_enabled')->default(true)->after('payment_deadline_hours');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn('payment_transfer_enabled');
        });
    }
};
