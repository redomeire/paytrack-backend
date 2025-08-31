<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\BillCategoriesSeeder;
use Database\Seeders\BillsSeeder;
use Database\Seeders\NotificationReadSeeder;
use Database\Seeders\NotificationSeeder;
use Database\Seeders\NotificationTypeSeeder;
use Database\Seeders\PaymentsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BillCategoriesSeeder::class,
            BillsSeeder::class,
            PaymentsSeeder::class,
            NotificationTypeSeeder::class,
            NotificationSeeder::class,
            NotificationReadSeeder::class,
        ]);
    }
}
