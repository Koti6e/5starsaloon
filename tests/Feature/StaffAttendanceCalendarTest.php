<?php

namespace Tests\Feature;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_shows_independent_staff_statuses_and_individual_colours(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff1 = User::factory()->create(['name' => 'Staff One', 'username' => 'staff1', 'role' => 'staff', 'status' => 'active']);
        $staff2 = User::factory()->create(['name' => 'Staff Two', 'username' => 'staff2', 'role' => 'staff', 'status' => 'active']);
        $dates = collect([4, 3, 2, 1])->map(fn (int $daysAgo) => now('Asia/Kolkata')->subDays($daysAgo)->toDateString());

        foreach ([
            [$staff1, $staff2],
            [$staff1, null],
            [null, $staff2],
            [null, null],
        ] as $index => [$present1, $present2]) {
            foreach ([[$present1, 'present'], [$present2, 'present']] as [$member, $status]) {
                if ($member) {
                    StaffAttendance::query()->create([
                        'staff_id' => $member->id,
                        'attendance_date' => $dates[$index],
                        'status' => $status,
                        'selfie_path' => $index === 0 && $member->is($staff1) ? 'staff-selfies/test/staff1.jpg' : null,
                    ]);
                }
            }
        }

        $response = $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk();
        $calendar = $response->viewData('attendanceCalendar')->keyBy('date');

        foreach ($dates as $index => $date) {
            $details = $calendar->get($date)['details'];
            self::assertSame($index === 0 || $index === 1, $details[0]['present']);
            self::assertSame($index === 0 || $index === 2, $details[1]['present']);
            self::assertSame('blue', $details[0]['color']);
            self::assertSame('yellow', $details[1]['color']);
            self::assertSame($details[0]['present'] ? 'Present' : 'Absent', $details[0]['status']);
            self::assertSame($details[1]['present'] ? 'Present' : 'Absent', $details[1]['status']);
        }

        $staff1DayOneAttendance = StaffAttendance::query()
            ->where('staff_id', $staff1->id)
            ->whereDate('attendance_date', $dates[0])
            ->firstOrFail();
        self::assertSame(route('admin.attendance.selfie', $staff1DayOneAttendance), $calendar->get($dates[0])['details'][0]['selfie_url']);

        $response->assertSee('Staff1 – Present')
            ->assertSee('Staff2 – Present')
            ->assertSee('bg-blue-600')
            ->assertSee('bg-yellow-400')
            ->assertSee('bg-red-600')
            ->assertSee('grid grid-cols-7 gap-1.5', false)
            ->assertSee(route('admin.attendance.selfie', $staff1DayOneAttendance), false)
            ->assertDontSee('All present')
            ->assertDontSee('Mixed');
    }
}
