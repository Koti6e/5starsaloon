<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', [
            'staff' => User::query()
                ->where('role', 'staff')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', ['temporaryPassword' => Str::password(12)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'lowercase', 'max:255', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'mobile' => ['required', 'string', 'max:30'],
            'employee_code' => ['nullable', 'string', 'max:255', Rule::unique('users', 'employee_code')],
            'specialization' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:10'],
            'is_home_service_eligible' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        User::query()->create([
            ...$validated,
            'role' => 'staff',
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
            'is_home_service_eligible' => $request->boolean('is_home_service_eligible'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account created. Share the temporary password securely; it will not be shown again.')
            ->with('temporary_password', $validated['password'])
            ->with('temporary_username', $validated['username']);
    }
}
