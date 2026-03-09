<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_companies', function (Blueprint $table) {
            $table->string('license_details', 500)->nullable()->after('legal_address')
                ->comment('Реквизиты лицензии на обращение с отходами (Таблица 3, ст. 14 ЖУДО)');
        });
    }

    public function down(): void
    {
        Schema::table('user_companies', function (Blueprint $table) {
            $table->dropColumn('license_details');
        });
    }
};
