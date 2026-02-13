<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('waste_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('user_companies')->onDelete('cascade');
            $table->string('fkko_code', 50);
            $table->date('period');
            $table->decimal('quantity', 10, 3)->default(0);
            $table->string('unit', 20)->default('т');
            $table->timestamps();

            $table->unique(['company_id', 'fkko_code', 'period'], 'uk_waste_period');

            $table->foreign('fkko_code')->references('code')->on('fkko_codes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_balances');
    }
};
