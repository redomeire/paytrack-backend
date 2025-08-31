<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\bills;
use App\Models\bill_series;
use Illuminate\Support\Arr;
use App\Models\bill_categories;
use Illuminate\Database\Seeder;

class BillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();
        $billCategoryIds = bill_categories::pluck('id')->toArray();
        if (empty($userIds)) {
            $this->command->error('No users found. Please seed the User table first.');
            return;
        }

        foreach ($userIds as $userId) {
            bill_series::factory()
                ->count(2)
                ->create([
                    'user_id' => $userId,
                    // Use Arr::random() to pick a random category ID
                    'bill_category_id' => Arr::random($billCategoryIds),
                    'amount' => rand(50, 500) * 1000,
                    'currency' => 'IDR',
                ])->each(function ($series) use ($billCategoryIds) { // Pass billCategoryIds to the closure
                bills::factory()
                    ->count(3)
                    ->create([
                        'user_id' => $series->user_id,
                        'bill_category_id' => $series->bill_category_id,
                        'bill_series_id' => $series->id,
                    ]);
            });
        }

        $this->command->info('Bills seeded successfully!');
    }
}
