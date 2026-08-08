<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = Str::lower((string) env('DEFAULT_ADMIN_USERNAME', 'admin'));

        User::query()->updateOrCreate(
            ['username' => $username],
            [
                'name' => env('DEFAULT_ADMIN_NAME', 'Salon Administrator'),
                'email' => null,
                'role' => 'admin',
                'status' => 'active',
                'must_change_password' => true,
                'password' => Hash::make((string) env('DEFAULT_ADMIN_PASSWORD', 'Admin@Jarvis2026!')),
            ]
        );
    }
}
