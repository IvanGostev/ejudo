<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Setting::firstOrCreate(
            ['key' => 'subscription_price'],
            [
                'label' => 'Стоимость подписки (руб/мес)',
                'value' => '5000'
            ]
        );
    }
}
