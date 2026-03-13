<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('acts', function (Blueprint $table) {

            $table->string('act_type', 50)->default('transfer')->after('company_id');

            $table->unsignedInteger('act_number')->nullable()->after('act_type');

            $table->string('contract_details', 500)->nullable()->after('act_number');

            $table->index(['company_id', 'act_number']);
        });
    }

    public function down(): void
    {
        Schema::table('acts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'act_number']);
            $table->dropColumn(['act_type', 'act_number', 'contract_details']);
        });
    }
};
