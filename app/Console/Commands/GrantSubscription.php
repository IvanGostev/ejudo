<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:grant {email} {days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant or extend subscription for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $days = (int) $this->argument('days');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return;
        }

        $currentExpires = $user->subscription_ends_at;

        if ($currentExpires && $currentExpires->isFuture()) {
            $newExpires = $currentExpires->copy()->addDays($days);
            $this->info("Extending existing subscription.");
        } else {
            $newExpires = now()->addDays($days);
            $this->info("Creating new subscription.");
        }

        $user->update(['subscription_ends_at' => $newExpires]);

        $this->info("Subscription for {$user->email} active until " . $newExpires->format('d.m.Y H:i'));
    }
}
