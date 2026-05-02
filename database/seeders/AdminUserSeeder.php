<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
            ]
        );

        // Store plain password for admin reference
        UserPassword::firstOrCreate(
            ['user_id' => $user->id],
            ['plain_password' => 'admin12345']
        );
    }
}
