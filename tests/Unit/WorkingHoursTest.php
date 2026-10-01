<?php

namespace Tests\Unit;

use App\Support\WorkingHours;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class WorkingHoursTest extends TestCase
{
    public function test_open_status_uses_the_configured_time_range(): void
    {
        $settings = $this->settings(['Monday' => ['open' => true, 'opens' => '09:00', 'closes' => '18:00']]);

        self::assertTrue(WorkingHours::status(Carbon::parse('2026-10-05 09:00', 'Asia/Kolkata'), $settings));
        self::assertFalse(WorkingHours::status(Carbon::parse('2026-10-05 18:00', 'Asia/Kolkata'), $settings));
        self::assertFalse(WorkingHours::status(Carbon::parse('2026-10-06 10:00', 'Asia/Kolkata'), $settings));
    }

    public function test_overnight_hours_continue_into_a_closed_following_day(): void
    {
        $settings = $this->settings(['Monday' => ['open' => true, 'opens' => '22:00', 'closes' => '02:00']]);

        self::assertTrue(WorkingHours::status(Carbon::parse('2026-10-06 01:59', 'Asia/Kolkata'), $settings));
        self::assertFalse(WorkingHours::status(Carbon::parse('2026-10-06 02:00', 'Asia/Kolkata'), $settings));
    }

    public function test_missing_or_invalid_hours_are_not_reported_as_open(): void
    {
        $settings = ['weekly_working_hours' => json_encode(['Monday' => ['open' => true, 'opens' => '25:99', 'closes' => '18:00']])];

        self::assertFalse(WorkingHours::status(Carbon::parse('2026-10-05 12:00', 'Asia/Kolkata'), $settings));
        self::assertFalse(WorkingHours::status(Carbon::parse('2026-10-05 12:00', 'Asia/Kolkata'), []));
    }

    private function settings(array $days): array
    {
        return ['weekly_working_hours' => json_encode($days, JSON_THROW_ON_ERROR)];
    }
}
