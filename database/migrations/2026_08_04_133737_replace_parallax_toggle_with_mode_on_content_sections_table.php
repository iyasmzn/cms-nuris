<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Toggle parallax diganti mode tiga pilihan: tanpa efek, bergeser, atau
     * gambar terkunci ke layar.
     */
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->string('background_parallax_mode', 10)
                ->default('none')
                ->after('background_overlay')
                ->comment('none | scroll | fixed');
        });

        DB::table('content_sections')
            ->where('background_parallax', true)
            ->update(['background_parallax_mode' => 'scroll']);

        Schema::table('content_sections', function (Blueprint $table) {
            $table->dropColumn('background_parallax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->boolean('background_parallax')->default(false)->after('background_overlay');
        });

        DB::table('content_sections')
            ->where('background_parallax_mode', '!=', 'none')
            ->update(['background_parallax' => true]);

        Schema::table('content_sections', function (Blueprint $table) {
            $table->dropColumn('background_parallax_mode');
        });
    }
};
