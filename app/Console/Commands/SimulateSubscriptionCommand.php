<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Payment;
use App\Models\ReferralEarning;
use App\Models\Setting;
use Illuminate\Support\Str;

class SimulateSubscriptionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:subscribe {email : The email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate a successful subscription payment and process referral bonuses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        $this->info("Simulating subscription for: {$user->email}");

        $amount = (float) (Setting::where('key', 'subscription_price')->value('value') ?? 5000.00);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'period_months' => 1,
            'payment_system' => 'console_simulation',
            'status' => 'completed',
            'paid_at' => now(),
            'transaction_id' => 'CONSOLE_' . strtoupper(Str::random(10)),
        ]);
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
        $this->info("Subscription extended until: " . $newExpires->format('d.m.Y H:i'));
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
                    $this->info("Referral bonus of {$earningAmount} ₽ added to referrer: {$referrer->email}");
                }
            }
        } else {
            $this->warn("User has no referrer, skipping referral bonus.");
        }

        $this->info("Subscription simulation for {$user->email} completed successfully!");
        return 0;
    }
}
