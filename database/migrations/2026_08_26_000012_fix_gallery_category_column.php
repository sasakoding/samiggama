<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->string('category', 100)->default('Puja Bakti')->change();
        });

        // Ensure image_path / file_path compatibility on gallery_photos if needed
        if (Schema::hasTable('gallery_photos') && !Schema::hasColumn('gallery_photos', 'image_path')) {
            Schema::table('gallery_photos', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('file_path');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
