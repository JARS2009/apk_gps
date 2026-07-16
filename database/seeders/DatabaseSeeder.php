<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario super_admin por defecto. Credenciales: admin@apkgps.test / password
        User::firstOrCreate(
            ['email' => 'admin@apkgps.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ]
        );

        // Usuario con num_doc 76955031
        User::firstOrCreate(
            ['email' => 'ejemplo@gmail.com'],
            [
                'name' => 'Usuario 76955031',
                'num_doc' => '76955031',
                'password' => Hash::make('76955031'),
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            DemoSeeder::class,
        ]);
    }
}
