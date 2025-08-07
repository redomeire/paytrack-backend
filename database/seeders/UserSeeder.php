<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// protected $fillable = [
//         'first_name',
//         'last_name',
//         'phone',
//         'timezone',
//         'language',
//         'currency',
//         'email',
//         'password',
//     ];

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '1234567890',
                'timezone' => 'UTC',
                'language' => 'en',
                'currency' => 'USD',
                'email' => 'john@gmail.com',
                'password' => bcrypt('password123'),
            ],
            [
                'first_name' => 'Redo',
                'last_name' => 'Meire',
                'phone' => '1234567890',
                'timezone' => 'UTC',
                'language' => 'id',
                'currency' => 'IDR',
                'email' => 'redomeire2@gmail.com',
                'password' => bcrypt('Redomeire2105!'),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $this->command->info('Users seeded successfully!');
    }
}
