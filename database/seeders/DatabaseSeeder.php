<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            FkkoSeeder::class,
            PageSeeder::class,
            SettingSeeder::class,
            UserReferralCodeSeeder::class,
        ]);
    }
}
