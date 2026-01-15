<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('user_companies')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->integer('file_size')->nullable();
            $table->json('act_data'); // Full structure of the act
            $table->enum('status', ['uploaded', 'processing', 'processed', 'error'])->default('uploaded');
            $table->json('processing_result')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acts');
    }
};
