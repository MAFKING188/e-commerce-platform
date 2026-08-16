<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
    use Modules\IdentityAccess\Models\User;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


public function run()
{
    // Admin
    User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => Hash::make('password'),
        'role' => 'admin'
    ]);

    // Normal users
    User::create([
        'name' => 'John Doe',
        'email' => 'user1@test.com',
        'password' => Hash::make('password'),
        'role' => 'user'
    ]);

    User::create([
        'name' => 'Jane Doe',
        'email' => 'user2@test.com',
        'password' => Hash::make('password'),
        'role' => 'user'
    ]);
}
 
}

