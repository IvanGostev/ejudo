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
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->unique()->nullable()->after('is_admin');
            $table->foreignId('referrer_id')->nullable()->after('referral_code')->constrained('users')->onDelete('set null');
            $table->decimal('referral_balance', 12, 2)->default(0)->after('referrer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn(['referral_code', 'referrer_id', 'referral_balance']);
        });
    }
};
