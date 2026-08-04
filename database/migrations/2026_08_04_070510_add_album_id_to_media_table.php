<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Albums used to be a free-text column on `media`, which allowed typos and
     * near-duplicates ("Wisuda 2025" vs "wisuda 2025"). Each distinct name
     * becomes an `albums` row and the media points at it instead.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('album_id')->nullable()->after('show_in_gallery')
                ->constrained('albums')->nullOnDelete();
        });

        $names = DB::table('media')
            ->whereNotNull('album')
            ->where('album', '!=', '')
            ->distinct()
            ->pluck('album');

        foreach ($names as $name) {
            $id = DB::table('albums')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('media')->where('album', $name)->update(['album_id' => $id]);
        }

        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('album');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('album', 100)->nullable()->after('show_in_gallery');
        });

        $albums = DB::table('albums')->pluck('name', 'id');

        foreach ($albums as $id => $name) {
            DB::table('media')->where('album_id', $id)->update(['album' => $name]);
        }

        Schema::table('media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('album_id');
        });
    }
};
