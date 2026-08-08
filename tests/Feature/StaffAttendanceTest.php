<?php

namespace Tests\Feature;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_marks_attendance_once(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', [
            'username' => $staff->username,
            'password' => 'password',
        ])->assertRedirect(route('staff.dashboard', absolute: false));

        $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame(now('Asia/Kolkata')->toDateString(), $attendance->attendance_date->toDateString());
        $this->assertSame('present', $attendance->status);
        $this->assertSame('automatic_login', $attendance->source);

        $this->post('/logout');

        $this->post('/login', [
            'username' => $staff->username,
            'password' => 'password',
        ]);

        $this->assertSame(1, StaffAttendance::query()->where('staff_id', $staff->id)->count());
    }

    public function test_staff_logout_records_checkout_for_auto_row(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', [
            'username' => $staff->username,
            'password' => 'password',
        ]);

        $this->post('/logout')->assertRedirect('/');

        $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertNotNull($attendance->check_out_time);
        $this->assertSame('automatic_logout', $attendance->source);
    }

    public function test_admin_login_does_not_mark_staff_attendance(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', [
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertDatabaseCount('staff_attendances', 0);
    }
}
