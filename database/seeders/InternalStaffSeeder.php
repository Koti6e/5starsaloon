<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InternalStaffSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['staff1', 'staff2'] as $username) {
            User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'name' => strtoupper($username),
                    'email' => null,
                    'mobile' => null,
                    'role' => 'staff',
                    'status' => 'active',
                    'must_change_password' => false,
                    'password' => Hash::make('password'),
                ],
            );
        }
    }
}
