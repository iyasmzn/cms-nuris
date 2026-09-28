<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Existing buttons keep showing on every page: `all` is the default mode.
     */
    public function up(): void
    {
        Schema::table('floating_buttons', function (Blueprint $table) {
            $table->string('display_mode', 10)->default('all')->after('is_active');
            $table->json('display_targets')->nullable()->after('display_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('floating_buttons', function (Blueprint $table) {
            $table->dropColumn(['display_mode', 'display_targets']);
        });
    }
};
