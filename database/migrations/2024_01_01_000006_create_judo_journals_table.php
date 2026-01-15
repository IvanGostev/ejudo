<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('judo_journals', function (Blueprint $table) {
            $table->id();
            $table->date('period');
            $table->foreignId('company_id')->constrained('user_companies')->onDelete('cascade');
            $table->enum('role', ['waste_generator', 'waste_processor']);
            $table->json('table1_data'); // Composition
            $table->json('table2_data'); // Summary
            $table->json('table3_data'); // Transferred
            $table->json('table4_data'); // Received
            $table->string('pdf_path', 500)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->dateTime('downloaded_at')->nullable();
            $table->timestamps();

            $table->index('period');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judo_journals');
    }
};
