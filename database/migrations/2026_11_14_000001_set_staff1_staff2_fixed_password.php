<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'staff')
            ->whereIn(DB::raw('LOWER(username)'), ['staff1', 'staff2'])
            ->update([
                'password' => Hash::make('5starnewlook'),
                'must_change_password' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Credential hashes cannot be restored. Reverting this migration does not alter the password.
    }
};
