<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tab baru ditentukan admin lewat saklar, bukan ditebak dari alamatnya —
     * sama seperti tombol CTA pada seksi dinamis.
     */
    public function up(): void
    {
        Schema::table('stats', function (Blueprint $table) {
            $table->boolean('url_new_tab')->default(false)->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('stats', function (Blueprint $table) {
            $table->dropColumn('url_new_tab');
        });
    }
};
