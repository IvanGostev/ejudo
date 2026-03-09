<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Расширяем точность area и capacity: decimal(15,3) вмещает до 999 999 999 999.999
     */
    public function up(): void
    {
        Schema::table('polygons', function (Blueprint $table) {
            $table->decimal('area',     15, 3)->nullable()->change();
            $table->decimal('capacity', 15, 3)->nullable()->change();
            $table->decimal('current_load', 15, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('polygons', function (Blueprint $table) {
            $table->decimal('area',     10, 2)->nullable()->change();
            $table->decimal('capacity', 10, 2)->nullable()->change();
            $table->decimal('current_load', 10, 2)->default(0)->change();
        });
    }
};
