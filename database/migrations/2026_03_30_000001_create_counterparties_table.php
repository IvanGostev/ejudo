<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterparties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('inn', 12)->nullable();
            $table->string('kpp', 9)->nullable();
            $table->string('legal_address')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_valid_until')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('user_companies')->onDelete('cascade');
            $table->index(['company_id', 'inn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterparties');
    }
};
