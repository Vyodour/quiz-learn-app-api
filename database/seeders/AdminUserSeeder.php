<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $adminUser = User::firstOrCreate(
            [
                'email' => 'admin1@gmail.com',
            ],
            [
                'name' => 'Admin Pertama',
                'username' => 'admin1',
                'password' => Hash::make('password')
            ]
        );

        $adminUser->assignRole($adminRole);

    }
}
