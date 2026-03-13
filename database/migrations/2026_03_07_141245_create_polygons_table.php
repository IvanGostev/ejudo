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
        Schema::create('polygons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('user_companies')->onDelete('cascade');
            $table->string('name');
            $table->text('address');
            $table->text('description')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->json('waste_types')->nullable();
            $table->decimal('capacity', 10, 2)->nullable();
            $table->decimal('current_load', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('coordinates')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polygons');
    }
};
