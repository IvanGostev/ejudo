<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant admin access to a user by phone number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $this->error("User with phone {$phone} not found.");
            return;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("User {$user->phone} is now an admin.");
    }
}
