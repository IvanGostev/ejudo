<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify SMTP configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Attempting to send test email to: {$email}");

        try {
            Mail::raw('This is a test email from eJydo.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('eJydo SMTP Test');
            });

            $this->info('Email sent successfully!');
        } catch (\Throwable $e) {
            $this->error('Failed to send email.');
            $this->error('Error Message: ' . $e->getMessage());
            // $this->error('Trace: ' . $e->getTraceAsString());
        }
    }
}
