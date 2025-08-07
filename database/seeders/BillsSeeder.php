<?php

namespace Database\Seeders;

use App\Models\bills;
use App\Models\bill_categories;
use App\Models\User;
use Illuminate\Database\Seeder;

// protected $fillable = [
//         'user_id',
//         'bill_category_id',
//         'name',
//         'description',
//         'amount',
//         'currency',
//         'billing_type',
//         'frequency',
//         'custom_frequency_days',
//         'first_due_date',
//         'next_due_date',
//         'last_paid_date',
//         'auto_advance',
//         'notes',
//         'attachment_url',
//     ];

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
            bills::factory()
                ->count(2)
                ->create([
                    'user_id' => $userId,
                    'bill_category_id' => $billCategoryIds[0],
                    'name' => 'Sample Bill ' . rand(1, 100),
                    'amount' => rand(1000, 10000),
                    'currency' => 'USD',
                    'billing_type' => 'fixed',
                    'frequency' => 'monthly',
                    'custom_frequency_days' => 1,
                    'first_due_date' => now()->addDays(rand(1, 30)),
                    'next_due_date' => now()->addDays(rand(31, 60)),
                    'last_paid_date' => now()->subDays(rand(1, 30)),
                    'auto_advance' => true,
                    'notes' => 'This is a sample bill for user ID: ' . $userId,
                    'attachment_url' => null,
                ]);
        }

        $this->command->info('Bills seeded successfully!');
    }
}
