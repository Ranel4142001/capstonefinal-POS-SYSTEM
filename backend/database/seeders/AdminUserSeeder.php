<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminExists = DB::table('users')->where('role', 'admin')->exists();

        if ($adminExists) {
            return;
        }

        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'admin123');
        $email = env('ADMIN_EMAIL');

        DB::table('users')->insert([
            'username' => $username,
            'email' => $email ?: null,
            'password_hash' => Hash::make($password),
            'role' => 'admin',
            'created_at' => now(),
        ]);
    }
}