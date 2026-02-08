<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Payment;
use App\Models\ReferralEarning;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SimulateSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Use: php artisan db:seed --class=SimulateSubscriptionSeeder --env=EMAIL=user@example.com
     */
    public function run(): void
    {
        // Try to get email from environment or use the first user
        $email = env('EMAIL');

        $user = $email ? User::where('email', $email)->first() : User::first();

        if (!$user) {
            $this->command->error("User not found.");
            return;
        }

        $this->command->info("Simulating subscription for: {$user->email}");

        $amount = (float) (Setting::where('key', 'subscription_price')->value('value') ?? 5000.00);

        // 1. Create completed payment
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'period_months' => 1,
            'payment_system' => 'simulated',
            'status' => 'completed',
            'paid_at' => now(),
            'transaction_id' => 'SIM_' . strtoupper(\Illuminate\Support\Str::random(10)),
        ]);

        // 2. Extend subscription
        $currentExpires = $user->subscription_ends_at;
        if (is_string($currentExpires)) {
            $currentExpires = \Illuminate\Support\Facades\Date::parse($currentExpires);
        }

        if ($currentExpires && $currentExpires->isFuture()) {
            $newExpires = $currentExpires->copy()->addDays(30);
        } else {
            $newExpires = now()->addDays(30);
        }

        $user->update(['subscription_ends_at' => $newExpires]);
        $this->command->info("Subscription extended until: " . $newExpires->format('d.m.Y H:i'));

        // 3. Process Referral
        if ($user->referrer_id) {
            $referrer = $user->referrer;
            if ($referrer) {
                $referralPercent = (float) (Setting::where('key', 'referral_percent')->value('value') ?? 10.0);
                $earningAmount = round(($payment->amount * $referralPercent) / 100, 2);

                if ($earningAmount > 0) {
                    ReferralEarning::create([
                        'user_id' => $referrer->id,
                        'referral_id' => $user->id,
                        'payment_id' => $payment->id,
                        'amount' => $earningAmount,
                        'percent' => $referralPercent,
                    ]);

                    $referrer->increment('referral_balance', $earningAmount);
                    $this->command->info("Referral bonus of {$earningAmount} ₽ added to referrer: {$referrer->email}");
                }
            }
        } else {
            $this->command->warn("User has no referrer, skipping referral bonus.");
        }

        $this->command->success("Subscription simulation completed successfully!");
    }
}
