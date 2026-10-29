<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Role: Admin
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('admin123'),
                'role'              => 'admin',
                'phone'             => '081234567890',
                'address'           => 'Kantor Pengelola SIMASU',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.inventaris@gmail.com'],
            [
                'name'              => 'Admin Inventaris & Sarpras',
                'password'          => Hash::make('admin123'),
                'role'              => 'admin',
                'phone'             => '081234567891',
                'address'           => 'Gedung Sarpras Lt. 2',
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Role: User (Non-Admin / Pemohon)
        User::updateOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name'              => 'User Regular',
                'password'          => Hash::make('password123'),
                'role'              => 'user',
                'phone'             => '081234567892',
                'address'           => 'Asrama Mahasiswa Blok A',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'ahmad.fauzi@gmail.com'],
            [
                'name'              => 'Ahmad Fauzi',
                'password'          => Hash::make('password123'),
                'role'              => 'user',
                'phone'             => '081234567893',
                'address'           => 'Jl. Melati No. 10',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'siti.aminah@gmail.com'],
            [
                'name'              => 'Siti Aminah',
                'password'          => Hash::make('password123'),
                'role'              => 'user',
                'phone'             => '081234567894',
                'address'           => 'Jl. Mawar No. 5',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'budi.santoso@gmail.com'],
            [
                'name'              => 'Budi Santoso',
                'password'          => Hash::make('password123'),
                'role'              => 'user',
                'phone'             => '081234567895',
                'address'           => 'Jl. Kenanga No. 12',
                'email_verified_at' => now(),
            ]
        );
    }
}
