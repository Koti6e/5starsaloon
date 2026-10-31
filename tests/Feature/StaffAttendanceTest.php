<?php

namespace Tests\Feature;

use App\Models\StaffAttendance;
use App\Models\AttendanceVerificationAttempt;
use App\Models\StaffAttendanceDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_requires_gps_selfie_and_marks_attendance_once(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', [
            'username' => $staff->username,
            'password' => 'password',
        ])->assertRedirect(route('staff.selfie.create', absolute: false));

        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn())
            ->assertRedirect(route('staff.dashboard', absolute: false));

        $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame(now('Asia/Kolkata')->toDateString(), $attendance->attendance_date->toDateString());
        $this->assertSame('present', $attendance->status);
        $this->assertSame('selfie_login', $attendance->source);
        $this->assertSame('review_required', $attendance->verification_status);
        $this->assertSame('unavailable', $attendance->face_verification_status);

        $this->post('/logout');

        $this->post('/login', [
            'username' => $staff->username,
            'password' => 'password',
        ]);

        $this->assertSame(1, StaffAttendance::query()->where('staff_id', $staff->id)->count());
    }

    public function test_staff_logout_records_checkout_for_verified_checkin_row(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', [
            'username' => $staff->username,
            'password' => 'password',
        ])->assertRedirect(route('staff.selfie.create', absolute: false));
        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn());

        $this->post('/logout')->assertRedirect(route('login', absolute: false));

        $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertNotNull($attendance->check_out_time);
        $this->assertSame('selfie_login', $attendance->source);
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

    public function test_admin_monthly_attendance_summary_and_selected_staff_detail_use_existing_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false, 'weekly_off' => 'Sunday']);
        foreach ([
            ['2026-08-03', 'present'], ['2026-08-04', 'absent'], ['2026-08-05', 'leave'], ['2026-08-06', 'half_day'],
        ] as [$date, $status]) {
            StaffAttendance::query()->create(['staff_id' => $staff->id, 'attendance_date' => $date, 'status' => $status]);
        }

        $this->actingAs($admin)->get(route('admin.attendance.index', ['month' => '2026-08', 'staff' => $staff->id]))
            ->assertOk()
            ->assertSee('Monthly Summary')
            ->assertSee('Working Days')
            ->assertSee($staff->name)
            ->assertSee('26')
            ->assertSee('5.8%')
            ->assertSee('Aug 3');
    }

    public function test_server_rejects_outside_geofence_and_records_failed_attempt(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->post('/login', ['username' => $staff->username, 'password' => 'password'])
            ->assertRedirect(route('staff.selfie.create', absolute: false));

        $this->from(route('staff.selfie.create'))->post(route('staff.selfie.store'), $this->verifiedCheckIn([
            'latitude' => '12.9000000', 'longitude' => '80.0808760',
        ]))->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('staff_attendances', 0);
        $attempt = AttendanceVerificationAttempt::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame('rejected', $attempt->decision);
        $this->assertGreaterThan(200, (float) $attempt->distance_from_salon_meters);
    }

    public function test_admin_can_approve_exception_and_the_override_is_audited(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $this->post('/login', ['username' => $staff->username, 'password' => 'password']);
        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn([
            'latitude' => '12.9000000', 'longitude' => '80.0808760',
        ]))->assertSessionHasErrors('attendance');
        $attempt = AttendanceVerificationAttempt::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.attendance.verification.review', $attempt), [
            'decision' => 'approved', 'review_reason' => 'Administrator confirmed the staff member was at the salon.',
        ])->assertRedirect();

        $this->assertDatabaseHas('staff_attendances', [
            'staff_id' => $staff->id, 'status' => 'present', 'verification_status' => 'verified',
            'marked_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('staff_attendance_audits', [
            'staff_id' => $staff->id, 'admin_id' => $admin->id, 'action' => 'verification_override',
            'new_verification_status' => 'verified',
        ]);
    }

    public function test_device_registration_stays_pending_until_admin_approval(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $this->post('/login', ['username' => $staff->username, 'password' => 'password']);
        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn())->assertRedirect(route('staff.dashboard', absolute: false));
        $device = StaffAttendanceDevice::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame('pending', $device->status);

        $this->actingAs($admin)->patch(route('admin.attendance.devices.update', $device), [
            'action' => 'approve', 'reason' => 'Verified device change with the employee in person.',
        ])->assertRedirect();

        $this->assertSame('approved', $device->fresh()->status);
        $this->assertDatabaseHas('staff_attendance_device_events', [
            'device_id' => $device->id, 'admin_id' => $admin->id,
            'previous_status' => 'pending', 'action' => 'approved',
        ]);
    }

    private function verifiedCheckIn(array $overrides = []): array
    {
        return array_merge([
            'selfie_image' => 'data:image/jpeg;base64,'.base64_encode(random_bytes(12_000)),
            'latitude' => '12.8821489',
            'longitude' => '80.0808760',
            'gps_accuracy_meters' => '15',
            'device_key' => (string) Str::uuid(),
            'consent' => '1',
        ], $overrides);
    }
}
