<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'demostaff'],
            [
                'name' => 'Demo Staff',
                'email' => null,
                'mobile' => '9000000001',
                'role' => 'staff',
                'status' => 'active',
                'must_change_password' => true,
                'password' => Hash::make('Demo@Staff2026'),
            ],
        );

        if (class_exists(\App\Models\Customer::class)) {
            \App\Models\Customer::query()->updateOrCreate(
                ['mobile' => '9000000000'],
                [
                    'customer_code' => 'CUS-DEMO-0001',
                    'name' => 'Demo Customer',
                    'status' => 'active',
                ],
            );
        }

        Service::query()->where('slug', 'haircut')->first();

        $this->command?->info('Demo staff and customer are ready. No demo financial data was created.');
    }
}
