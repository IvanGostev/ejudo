<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fkko_codes', function (Blueprint $table) {
            $table->text('origin')->nullable()->comment('Происхождение или условия образования вида отхода');
            $table->string('aggregate_state', 200)->nullable()->comment('Агрегатное состояние и физическая форма');
            $table->text('chemical_composition')->nullable()->comment('Химический и компонентный состав, %');
        });
    }

    public function down(): void
    {
        Schema::table('fkko_codes', function (Blueprint $table) {
            $table->dropColumn(['origin', 'aggregate_state', 'chemical_composition']);
        });
    }
};
