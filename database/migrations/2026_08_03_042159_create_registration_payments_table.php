<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One tagihan pendaftaran per registration. Tahap 1 only settles these
     * manually (transfer + bukti diunggah pendaftar + verifikasi panitia); the
     * `provider`/`reference`/`payload` columns are already in place so a
     * payment gateway can be plugged in later without another migration.
     */
    public function up(): void
    {
        Schema::create('registration_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spmb_registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->nullable()->unique();

            $table->unsignedInteger('amount');
            $table->unsignedSmallInteger('unique_code')->default(0);

            $table->string('method')->default('manual_transfer'); // manual_transfer | gateway
            $table->string('status')->default('unpaid')->index(); // unpaid | waiting_verification | paid | rejected | expired

            // Diisi pendaftar saat mengunggah bukti transfer.
            $table->string('bank_account')->nullable();
            $table->string('sender_name')->nullable();
            $table->date('transferred_on')->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // Diisi panitia saat memverifikasi.
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();

            $table->timestamp('expires_at')->nullable();

            // Disiapkan untuk integrasi payment gateway (Tahap 2).
            $table->string('provider')->nullable();
            $table->string('reference')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_payments');
    }
};
