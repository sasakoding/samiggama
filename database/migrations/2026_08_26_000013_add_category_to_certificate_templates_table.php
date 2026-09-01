<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_templates', 'category')) {
                $table->string('category', 50)->default('umum')->after('name');
            }
            $table->unsignedBigInteger('min_amount')->default(0)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_templates', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
