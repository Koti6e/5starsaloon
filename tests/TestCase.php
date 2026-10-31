<?php

namespace Tests;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function actingAs($user, $guard = null): static
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User && $user->isStaff() && Schema::hasTable('staff_attendances')) {
            $now = now('Asia/Kolkata');
            $attendance = StaffAttendance::query()->firstOrCreate(
                ['staff_id' => $user->id, 'attendance_date' => $now->toDateString()],
                [
                    'status' => 'present',
                    'check_in_time' => $now->format('H:i:s'),
                    'source' => 'test_verified',
                    'selfie_captured_at' => $now,
                ],
            );

            $this->withSession([
                'staff_login_date' => $now->toDateString(),
                'staff_selfie_attendance_id' => $attendance->id,
            ]);
        }

        return $this;
    }
}
