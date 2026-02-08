<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UserReferralCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNull('referral_code')->orWhere('referral_code', '')->get();

        $count = 0;
        foreach ($users as $user) {
            do {
                $code = strtoupper(Str::random(8));
            } while (User::where('referral_code', $code)->exists());

            $user->referral_code = $code;
            $user->save();
            $count++;
        }

        $this->command->info("Successfully updated {$count} users with referral codes.");
    }
}
