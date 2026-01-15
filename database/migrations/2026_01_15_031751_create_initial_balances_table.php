<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('initial_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('user_companies')->cascadeOnDelete();
            $table->string('waste_name');
            $table->string('fkko_code')->nullable();
            $table->string('hazard_class')->nullable();
            $table->decimal('amount', 15, 3)->default(0);
            $table->year('year'); // Balance at start of which year? or date? simplified to year or period?
            // Actually, usually Initial Balance is relevant for the first report period. 
            // Let's store period 'YYYY-MM-01' to align with journals.
            $table->date('period');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_balances');
    }
};
