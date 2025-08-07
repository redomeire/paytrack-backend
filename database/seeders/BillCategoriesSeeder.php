<?php

namespace Database\Seeders;

use App\Models\bill_categories;
use Illuminate\Database\Seeder;

class BillCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            bill_categories::create(['name' => $category]);
        }

        $this->command->info('Bill categories seeded successfully!');
    }
}
