<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which jenjang a panel user handles. Data pendaftar dan tagihan hanya
     * boleh dilihat oleh unitnya sendiri, dan satu akun boleh memegang lebih
     * dari satu unit — jadi pivot, bukan kolom di `users`.
     */
    public function up(): void
    {
        Schema::create('institution_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['institution_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_user');
    }
};
