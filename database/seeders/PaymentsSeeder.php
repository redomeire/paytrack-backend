<?php

namespace Database\Seeders;

use App\Models\bills;
use App\Models\payments;
use Illuminate\Database\Seeder;

class PaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billIds = bills::pluck('id')->toArray();

        foreach ($billIds as $billId) {
            payments::factory()->create(
                [
                    'bill_id' => $billId,
                    'amount' => rand(1000, 50000) / 100,
                    'currency' => 'USD',
                    'paid_date' => now()->subDays(rand(1, 30)),
                    'due_date' => now()->addDays(rand(1, 30)),
                    'payment_method' => 'Credit Card',
                    'payment_reference' => 'REF-random-' . rand(1000, 9999),
                    'notes' => 'Payment for bill ID: ' . $billId,
                ]
            );
        }

        $this->command->info('Payments seeded successfully.');
    }
}
