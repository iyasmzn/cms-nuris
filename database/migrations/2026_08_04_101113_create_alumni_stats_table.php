<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alumni_stats', function (Blueprint $table) {
            $table->id();
            $table->string('icon', 20)->default('🎓');
            $table->string('icon_image')->nullable();
            $table->string('label', 100);
            $table->string('value', 50);
            $table->string('sub', 150)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_stats');
    }
};
