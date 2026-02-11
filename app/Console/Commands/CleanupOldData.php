<?php

namespace App\Console\Commands;

use App\Models\Act;
use App\Models\JudoJournal;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:old-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete acts and journals based on user subscription (30 days or 5 years)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data cleanup...');
        User::with('companies')->chunk(100, function ($users) {
            foreach ($users as $user) {

                $isSubscribed = $user->subscription_ends_at && $user->subscription_ends_at->isFuture();
                $cutoffDate = $isSubscribed ? now()->subYears(5) : now()->subDays(30);

                if ($user->companies->isEmpty()) {
                    continue;
                }

                $companyIds = $user->companies->pluck('id');
                $actsToDelete = Act::whereIn('company_id', $companyIds)
                    ->where('created_at', '<', $cutoffDate)
                    ->get();

                foreach ($actsToDelete as $act) {

                    if ($act->filename && Storage::disk('local')->exists($act->filename)) {
                        Storage::disk('local')->delete($act->filename);
                    }
                    $act->delete();
                }

                if ($actsToDelete->isNotEmpty()) {
                    $this->info("User {$user->phone}: Deleted {$actsToDelete->count()} acts (Subscription: " . ($isSubscribed ? 'Yes' : 'No') . ")");
                }
                $journalsDeleted = JudoJournal::whereIn('company_id', $companyIds)
                    ->where('created_at', '<', $cutoffDate)
                    ->delete();

                if ($journalsDeleted > 0) {
                    $this->info("User {$user->phone}: Deleted {$journalsDeleted} journals.");
                }
            }
        });

        $this->info('Data cleanup completed.');
    }
}
