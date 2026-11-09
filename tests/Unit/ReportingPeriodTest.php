<?php

namespace Tests\Unit;

use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ReportingPeriodTest extends TestCase
{
    public function test_day_period_uses_ist_day_boundaries_and_query_round_trip(): void
    {
        $period = ReportingPeriod::fromInput(['period' => 'daily', 'date' => '2026-10-05']);

        self::assertSame('2026-10-05 00:00:00', $period->start->format('Y-m-d H:i:s'));
        self::assertSame('2026-10-06 00:00:00', $period->endExclusive->format('Y-m-d H:i:s'));
        self::assertSame('Asia/Kolkata', $period->timezone);
        self::assertSame($period->toQuery(), ReportingPeriod::fromInput($period->toQuery())->toQuery());
    }

    public function test_week_starts_monday_and_handles_year_boundary(): void
    {
        $period = ReportingPeriod::fromInput(['period' => 'weekly', 'week' => '2027-01-01']);

        self::assertSame('2026-12-28', $period->start->toDateString());
        self::assertSame('2027-01-04', $period->endExclusive->toDateString());
        self::assertSame('2026-12-21', $period->previous()->start->toDateString());
        self::assertSame('2027-01-04', $period->next()->start->toDateString());
    }

    public function test_monthly_and_custom_periods_handle_leap_day_and_inclusive_end(): void
    {
        $month = ReportingPeriod::fromInput(['period' => 'monthly', 'month' => '2028-02']);
        $custom = ReportingPeriod::fromInput(['period' => 'custom', 'start_date' => '2028-02-28', 'end_date' => '2028-02-29']);

        self::assertSame('2028-02-01', $month->start->toDateString());
        self::assertSame('2028-03-01', $month->endExclusive->toDateString());
        self::assertSame('2028-03-01', $custom->endExclusive->toDateString());
        self::assertSame('2028-02-29', $custom->toQuery()['end_date']);
        self::assertSame('2028-02-26', $custom->previous()->start->toDateString());
        self::assertSame('2028-03-01', $custom->next()->start->toDateString());
    }

    public function test_live_mode_is_only_enabled_for_the_current_ist_day(): void
    {
        $now = CarbonImmutable::parse('2026-10-05 00:10:00', 'Asia/Kolkata');
        $today = ReportingPeriod::fromInput(['period' => 'daily', 'date' => '2026-10-05'], reference: $now);
        $yesterday = ReportingPeriod::fromInput(['period' => 'daily', 'date' => '2026-10-04'], reference: $now);

        self::assertTrue($today->isCurrentBusinessDay($now));
        self::assertFalse($yesterday->isCurrentBusinessDay($now));
    }
}
