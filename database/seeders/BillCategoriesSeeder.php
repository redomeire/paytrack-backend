<?php

namespace Database\Seeders;

use App\Models\bill_categories;
use App\Models\User;
use Illuminate\Database\Seeder;

class BillCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();
        $categories = [
            'Electricity',
            'Water',
            'Internet',
            'Gas',
            'Phone',
            'Rent',
            'Insurance',
            'Groceries',
            'Transportation',
            'Entertainment',
        ];

        foreach ($categories as $category) {
            bill_categories::create([
                'user_id' => $userIds[0],
                'name' => $category,
            ]);
        }

        $this->command->info('Bill categories seeded successfully!');
    }
}
