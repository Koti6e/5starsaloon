<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class StaffCredentialPolicy
{
    public static function usesFixedPassword(User $user): bool
    {
        return $user->isStaff() && in_array(Str::lower(trim((string) $user->username)), ['staff1', 'staff2'], true);
    }
}
