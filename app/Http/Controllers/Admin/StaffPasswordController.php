<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffPasswordController extends Controller
{
    public function edit(User $staff)
    {
        abort_unless($staff->role === 'staff', 404);

        return view('admin.staff.edit', ['staff' => $staff]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $staff->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.staff.index')->with('status', 'Staff password has been reset.');
    }
}
