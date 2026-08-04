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
        Schema::create('content_sections', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow', 60)->nullable()->comment('Teks kecil di atas judul');
            $table->string('title', 150);
            $table->text('description');
            $table->string('image')->nullable();
            $table->string('image_position', 10)->default('right')->comment('left | right');
            $table->string('background', 10)->default('default')->comment('default | alt');
            $table->string('anchor', 60)->nullable()->comment('ID anchor untuk tautan menu, misal fasilitas');
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('cta_new_tab')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_sections');
    }
};
