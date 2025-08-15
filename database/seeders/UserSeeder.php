<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Redo',
                'last_name' => 'Meire',
                'phone' => '1234567890',
                'timezone' => 'UTC',
                'currency' => 'IDR',
                'email' => 'test@gmail.com',
                'password' => bcrypt('test123'),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $this->command->info('Users seeded successfully!');
    }
}
