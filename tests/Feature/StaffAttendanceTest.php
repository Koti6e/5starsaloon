<?php

namespace Tests\Feature;

use App\Models\AttendanceVerificationAttempt;
use App\Models\StaffAttendance;
use App\Models\StaffAttendanceDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_staff1_login_requires_gps_and_records_rejected_attempts(): void
    {
        $staff = User::factory()->create(['username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', ['username' => 'staff1', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
        $attempt = AttendanceVerificationAttempt::query()->where('staff_id', $staff->id)->where('attempt_type', 'login_gps')->firstOrFail();
        $this->assertSame('login_gps', $attempt->attempt_type);
        $this->assertSame('rejected', $attempt->decision);
        $this->assertNull($attempt->latitude);
        $this->assertNull($attempt->distance_from_salon_meters);
        $this->assertDatabaseCount('staff_attendances', 0);
    }

    public function test_staff1_login_rejects_coordinates_outside_the_approved_radius(): void
    {
        $staff = User::factory()->create(['username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', ['username' => 'staff1', 'password' => 'password'] + $this->loginGps([
            'latitude' => '12.9000000',
            'longitude' => '80.0808760',
        ]))->assertSessionHasErrors('username');

        $this->assertGuest();
        $attempt = AttendanceVerificationAttempt::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame('rejected', $attempt->decision);
        $this->assertGreaterThan(200, (float) $attempt->distance_from_salon_meters);
    }

    public function test_staff1_login_location_attempt_is_audited_when_password_is_wrong(): void
    {
        $staff = User::factory()->create(['username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', ['username' => 'staff1', 'password' => 'wrong-password'] + $this->loginGps())
            ->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertDatabaseHas('attendance_verification_attempts', [
            'staff_id' => $staff->id,
            'attempt_type' => 'login_gps',
            'decision' => 'rejected',
            'reason' => 'GPS location passed; account credentials were rejected.',
        ]);
    }

    public function test_other_staff_keep_daily_camera_check_in_without_geofence_restriction(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['username' => 'stylist3', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->post('/login', ['username' => $staff->username, 'password' => 'password'])
            ->assertRedirect(route('staff.selfie.create', absolute: false));

        $checkIn = $this->verifiedCheckIn();
        unset($checkIn['latitude'], $checkIn['longitude'], $checkIn['gps_accuracy_meters']);

        $this->post(route('staff.selfie.store'), $checkIn)
            ->assertRedirect(route('staff.dashboard', absolute: false));

        $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertNotNull($attendance->selfie_captured_at);
        $this->assertNull($attendance->latitude);
        $this->assertSame('review_required', $attendance->verification_status);
    }

    public function test_admin_can_inspect_the_gps_login_audit_history(): void
    {
        $staff = User::factory()->create(['name' => 'Staff One', 'username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);

        $this->post('/login', ['username' => 'staff1', 'password' => 'password'] + $this->loginGps())
            ->assertRedirect(route('staff.selfie.create', absolute: false));

        $this->actingAs($admin)->get(route('admin.attendance.index'))
            ->assertOk()
            ->assertSee('Staff 1 and Staff 2 Login History')
            ->assertSee('Staff One')
            ->assertSee('GPS location and account credentials passed.')
            ->assertSee('12.8821489');
    }

    public function test_gps_is_required_on_every_staff1_login_and_selfie_is_required_once_per_business_day(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->travelTo(Carbon::parse('2026-11-05 10:00:00', 'Asia/Kolkata'));

        $this->post('/login', ['username' => 'staff1', 'password' => 'password'] + $this->loginGps())
            ->assertRedirect(route('staff.selfie.create', absolute: false));
        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn())
            ->assertRedirect(route('staff.dashboard', absolute: false));

        $this->post('/logout');
        $this->post('/login', ['username' => 'staff1', 'password' => 'password'] + $this->loginGps())
            ->assertRedirect(route('staff.dashboard', absolute: false));

        $this->post('/logout');
        $this->travel(1)->days();
        $this->post('/login', ['username' => 'staff1', 'password' => 'password'] + $this->loginGps())
            ->assertRedirect(route('staff.selfie.create', absolute: false));

        $attempts = AttendanceVerificationAttempt::query()->where('staff_id', $staff->id)->where('attempt_type', 'login_gps')->get();
        $this->assertCount(3, $attempts);
        $this->assertSame(['verified', 'verified', 'verified'], $attempts->pluck('decision')->all());
        $this->assertNotNull($attempts->first()->latitude);
    }

    public function test_weekly_off_does_not_skip_gps_or_daily_selfie_and_keeps_weekly_off_attendance_status(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create([
            'username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false, 'weekly_off' => 'Sunday',
        ]);
        $this->travelTo(Carbon::parse('2026-11-08 10:00:00', 'Asia/Kolkata'));

        $this->post('/login', ['username' => 'staff1', 'password' => 'password'] + $this->loginGps())
            ->assertRedirect(route('staff.selfie.create', absolute: false));
        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn())
            ->assertRedirect(route('staff.dashboard', absolute: false));

        $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame('weekly_off', $attendance->status);
        $this->assertNotNull($attendance->selfie_captured_at);
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

    public function test_admin_custom_attendance_range_uses_weekly_off_and_excludes_future_work_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false, 'weekly_off' => 'Sunday']);
        $this->travelTo(Carbon::parse('2026-10-07 10:00:00', 'Asia/Kolkata'));
        StaffAttendance::query()->create(['staff_id' => $staff->id, 'attendance_date' => '2026-10-05', 'status' => 'present']);
        StaffAttendance::query()->create(['staff_id' => $staff->id, 'attendance_date' => '2026-10-06', 'status' => 'absent']);
        StaffAttendance::query()->create(['staff_id' => $staff->id, 'attendance_date' => '2026-10-09', 'status' => 'present']);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', [
            'period' => 'custom', 'start_date' => '2026-10-05', 'end_date' => '2026-10-12',
        ]))->assertOk()->assertSee('Custom Range Attendance');

        $summary = $response->viewData('rangeSummary')->get($staff->id);
        $this->assertSame(3, $summary['working_days']);
        $this->assertSame(1, $summary['present']);
        $this->assertSame(1, $summary['absent']);
        $this->assertSame(33.3, $summary['attendance']);
    }

    public function test_server_rejects_outside_geofence_and_records_failed_attempt(): void
    {
        $staff = User::factory()->create(['username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->post('/login', ['username' => $staff->username, 'password' => 'password'] + $this->loginGps())
            ->assertRedirect(route('staff.selfie.create', absolute: false));

        $this->from(route('staff.selfie.create'))->post(route('staff.selfie.store'), $this->verifiedCheckIn([
            'latitude' => '12.9000000', 'longitude' => '80.0808760',
        ]))->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('staff_attendances', 0);
        $attempt = AttendanceVerificationAttempt::query()->where('staff_id', $staff->id)->where('attempt_type', 'attendance_selfie')->firstOrFail();
        $this->assertSame('rejected', $attempt->decision);
        $this->assertGreaterThan(200, (float) $attempt->distance_from_salon_meters);
    }

    public function test_admin_can_approve_exception_and_the_override_is_audited(): void
    {
        $staff = User::factory()->create(['username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $this->post('/login', ['username' => $staff->username, 'password' => 'password'] + $this->loginGps());
        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn([
            'latitude' => '12.9000000', 'longitude' => '80.0808760',
        ]))->assertSessionHasErrors('attendance');
        $attempt = AttendanceVerificationAttempt::query()->where('attempt_type', 'attendance_selfie')->firstOrFail();

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

    private function loginGps(array $overrides = []): array
    {
        return array_merge([
            'latitude' => '12.8821489',
            'longitude' => '80.0808760',
            'gps_accuracy_meters' => '15',
        ], $overrides);
    }
}
