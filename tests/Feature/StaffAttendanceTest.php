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
use RuntimeException;
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
            ->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));

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
            ->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));

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
            ->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));

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
            ->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));

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

    public function test_server_rejects_missing_gps_for_staff1_without_saving_attendance_or_selfie(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['username' => 'staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $submission = $this->verifiedCheckIn();
        unset($submission['latitude'], $submission['longitude'], $submission['gps_accuracy_meters']);

        $this->be($staff)
            ->from(route('staff.selfie.create'))
            ->post(route('staff.selfie.store'), $submission)
            ->assertRedirect(route('staff.selfie.create'))
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('staff_attendances', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('staff-selfies'));
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
        $this->post(route('staff.selfie.store'), $this->verifiedCheckIn())->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));
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

    public function test_device_label_is_truncated_to_the_database_limit_for_long_mobile_user_agents(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->withHeader('User-Agent', str_repeat('Android Chrome Mobile Browser ', 8))
            ->actingAs($staff)
            ->post(route('staff.selfie.store'), $this->verifiedCheckIn())
            ->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));

        $device = StaffAttendanceDevice::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertLessThanOrEqual(100, mb_strlen($device->label));
        $this->assertDatabaseCount('staff_attendances', 1);
    }

    public function test_valid_jpeg_and_png_selfies_are_stored_and_retrievable(): void
    {
        foreach (['jpeg', 'png', 'webp'] as $format) {
            Storage::fake('local');
            $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

            $this->actingAs($staff)->post(route('staff.selfie.store'), $this->verifiedCheckIn([
                'selfie_image' => $this->selfieImage($format),
                'device_key' => (string) Str::uuid(),
            ]))->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));

            $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
            $this->assertStringEndsWith('.'.$format, $attendance->selfie_path);
            Storage::disk('local')->assertExists($attendance->selfie_path);
            $this->assertSame(1, getimagesizefromstring(Storage::disk('local')->get($attendance->selfie_path))[2] > 0 ? 1 : 0);
        }
    }

    public function test_unsupported_or_corrupt_selfies_are_rejected_without_marking_attendance(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        foreach ([
            'data:image/gif;base64,'.base64_encode(str_repeat('g', 2000)),
            'data:image/jpeg;base64,'.base64_encode(str_repeat('x', 2000)),
        ] as $image) {
        $this->be($staff)
                ->from(route('staff.selfie.create'))
                ->post(route('staff.selfie.store'), $this->verifiedCheckIn(['selfie_image' => $image]))
                ->assertRedirect(route('staff.selfie.create'))
                ->assertSessionHasErrors('attendance');
        }

        $this->assertDatabaseCount('staff_attendances', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('staff-selfies'));
    }

    public function test_oversized_selfie_payload_is_rejected_without_marking_attendance(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->be($staff)
            ->from(route('staff.selfie.create'))
            ->post(route('staff.selfie.store'), $this->verifiedCheckIn([
                'selfie_image' => str_repeat('x', 6_000_001),
            ]))
            ->assertRedirect(route('staff.selfie.create'))
            ->assertSessionHasErrors('selfie_image');

        $this->assertDatabaseCount('staff_attendances', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('staff-selfies'));
    }

    public function test_database_failure_rolls_back_attendance_and_removes_only_the_new_selfie(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        AttendanceVerificationAttempt::creating(static function (): never {
            throw new RuntimeException('Simulated attendance persistence failure.');
        });

        $this->be($staff)
            ->from(route('staff.selfie.create'))
            ->post(route('staff.selfie.store'), $this->verifiedCheckIn())
            ->assertRedirect(route('staff.selfie.create'))
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('staff_attendances', 0);
        $this->assertDatabaseCount('staff_attendance_devices', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('staff-selfies'));
    }

    public function test_storage_failure_returns_actionable_error_and_does_not_mark_attendance(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $disk = \Mockery::mock();
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with(\Mockery::type('string'))->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->twice()->andReturn($disk);

        $this->be($staff)
            ->from(route('staff.selfie.create'))
            ->post(route('staff.selfie.store'), $this->verifiedCheckIn())
            ->assertRedirect(route('staff.selfie.create'))
            ->assertSessionHasErrors(['attendance' => 'The selfie could not be stored. Attendance was not marked. Retake the photo and try again; contact an administrator if the problem continues.']);

        $this->assertDatabaseCount('staff_attendances', 0);
        $this->assertDatabaseCount('staff_attendance_devices', 0);
    }

    public function test_duplicate_attendance_submission_is_idempotent(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $submission = $this->verifiedCheckIn();

        $this->actingAs($staff)->post(route('staff.selfie.store'), $submission)
            ->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));
        $path = StaffAttendance::query()->where('staff_id', $staff->id)->value('selfie_path');
        $this->post(route('staff.selfie.store'), $submission)
            ->assertRedirect(route('staff.dashboard', ['attendance_checkin' => 'saved'], absolute: false));

        $this->assertDatabaseCount('staff_attendances', 1);
        $this->assertDatabaseCount('attendance_verification_attempts', 1);
        $this->assertSame($path, StaffAttendance::query()->where('staff_id', $staff->id)->value('selfie_path'));
        $this->assertCount(1, Storage::disk('local')->allFiles('staff-selfies'));
    }

    public function test_selfie_submission_does_not_overwrite_existing_absence_or_manual_attendance_fields(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $existing = StaffAttendance::query()->create([
            'staff_id' => $staff->id,
            'attendance_date' => now('Asia/Kolkata')->toDateString(),
            'status' => 'absent',
            'source' => 'manual_admin',
            'marked_by' => $admin->id,
            'notes' => 'Admin attendance correction',
        ]);

        $this->be($staff)
            ->from(route('staff.selfie.create'))
            ->post(route('staff.selfie.store'), $this->verifiedCheckIn())
            ->assertRedirect(route('staff.selfie.create'))
            ->assertSessionHasErrors('attendance');

        $this->assertSame('absent', $existing->fresh()->status);
        $this->assertSame('manual_admin', $existing->fresh()->source);
        $this->assertSame($admin->id, $existing->fresh()->marked_by);
        $this->assertSame('Admin attendance correction', $existing->fresh()->notes);
        $this->assertNull($existing->fresh()->selfie_captured_at);
        $this->assertSame([], Storage::disk('local')->allFiles('staff-selfies'));
    }

    private function verifiedCheckIn(array $overrides = []): array
    {
        return array_merge([
            'selfie_image' => $this->selfieImage('jpeg'),
            'latitude' => '12.8821489',
            'longitude' => '80.0808760',
            'gps_accuracy_meters' => '15',
            'device_key' => (string) Str::uuid(),
            'consent' => '1',
        ], $overrides);
    }

    private function selfieImage(string $format): string
    {
        $image = imagecreatetruecolor(720, 960);
        imagefill($image, 0, 0, imagecolorallocate($image, 60, 100, 140));
        for ($step = 0; $step < 8; $step++) {
            imagefilledrectangle(
                $image,
                $step * 90,
                $step * 120,
                min(719, $step * 90 + 179),
                min(959, $step * 120 + 239),
                imagecolorallocate($image, 35 + $step * 20, 45 + $step * 13, 80 + $step * 16),
            );
        }

        ob_start();
        if ($format === 'png') {
            imagepng($image);
        } elseif ($format === 'webp') {
            imagewebp($image, null, 80);
        } else {
            imagejpeg($image, null, 85);
        }
        $binary = ob_get_clean();
        imagedestroy($image);

        return 'data:image/'.$format.';base64,'.base64_encode($binary);
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
