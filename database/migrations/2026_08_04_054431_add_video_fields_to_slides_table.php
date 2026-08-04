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
        Schema::table('slides', function (Blueprint $table) {
            $table->string('media_type', 20)->default('image')->after('id');
            $table->string('video_path')->nullable()->after('image');
            $table->string('video_url')->nullable()->after('video_path');
            $table->boolean('video_preview_enabled')->default(false)->after('video_url');
            $table->boolean('show_video_button')->default(false)->after('video_preview_enabled');
            $table->string('video_button_label', 100)->nullable()->after('show_video_button');
            $table->string('preview_video_url')->nullable()->after('video_button_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slides', function (Blueprint $table) {
            $table->dropColumn([
                'media_type',
                'video_path',
                'video_url',
                'video_preview_enabled',
                'show_video_button',
                'video_button_label',
                'preview_video_url',
            ]);
        });
    }
};
