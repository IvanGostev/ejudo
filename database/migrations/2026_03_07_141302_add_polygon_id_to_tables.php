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
        Schema::table('waste_balances', function (Blueprint $table) {
            $table->foreignId('polygon_id')->nullable()->constrained('polygons')->onDelete('set null');
            $table->index('polygon_id');
        });

        Schema::table('judo_journals', function (Blueprint $table) {
            $table->foreignId('polygon_id')->nullable()->constrained('polygons')->onDelete('set null');
            $table->index('polygon_id');


            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'polygon_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waste_balances', function (Blueprint $table) {
            $table->dropForeign(['polygon_id']);
            $table->dropColumn('polygon_id');
        });

        Schema::table('judo_journals', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'created_at']);
            $table->dropIndex(['company_id', 'polygon_id', 'created_at']);
            $table->dropForeign(['polygon_id']);
            $table->dropColumn('polygon_id');
        });
    }
};
