<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class ReportingPeriod
{
    public const TIMEZONE = 'Asia/Kolkata';

    private function __construct(
        public string $mode,
        public CarbonImmutable $start,
        public CarbonImmutable $endExclusive,
        public string $timezone,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromInput(
        array $input,
        string $defaultMode = 'daily',
        string $timezone = self::TIMEZONE,
        ?CarbonImmutable $reference = null,
    ): self {
        $now = ($reference ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $mode = (string) ($input['period'] ?? match (true) {
            isset($input['date']) => 'daily',
            isset($input['week']) => 'weekly',
            isset($input['month']) => 'monthly',
            isset($input['start_date'], $input['end_date']) => 'custom',
            default => $defaultMode,
        });

        return match ($mode) {
            'daily', 'day' => self::between('daily', CarbonImmutable::parse($input['date'] ?? $now->toDateString(), $timezone)->startOfDay(), 1, 'day', $timezone),
            'weekly', 'week' => self::between('weekly', CarbonImmutable::parse($input['week'] ?? $input['date'] ?? $now->toDateString(), $timezone)->startOfWeek(CarbonImmutable::MONDAY), 1, 'week', $timezone),
            'monthly', 'month' => self::between('monthly', CarbonImmutable::createFromFormat('!Y-m', (string) ($input['month'] ?? $now->format('Y-m')), $timezone), 1, 'month', $timezone),
            'custom' => self::custom($input, $timezone),
            default => throw new InvalidArgumentException('Unsupported reporting period.'),
        };
    }

    public function previous(): ?self
    {
        if ($this->mode === 'custom') {
            $days = (int) $this->start->diffInDays($this->endExclusive);

            return self::custom([
                'start_date' => $this->start->subDays($days)->toDateString(),
                'end_date' => $this->start->subDay()->toDateString(),
            ], $this->timezone);
        }

        $previousStart = match ($this->mode) {
            'daily' => $this->start->subDay(),
            'weekly' => $this->start->subWeek(),
            default => $this->start->subMonth(),
        };

        return self::between($this->mode, $previousStart, 1, $this->unit(), $this->timezone);
    }

    public function next(): ?self
    {
        if ($this->mode === 'custom') {
            $days = (int) $this->start->diffInDays($this->endExclusive);

            return self::custom([
                'start_date' => $this->endExclusive->toDateString(),
                'end_date' => $this->endExclusive->addDays($days - 1)->toDateString(),
            ], $this->timezone);
        }

        return self::between($this->mode, $this->endExclusive, 1, $this->unit(), $this->timezone);
    }

    /** @return array<string, string> */
    public function toQuery(): array
    {
        return match ($this->mode) {
            'daily' => ['period' => 'daily', 'date' => $this->start->toDateString()],
            'weekly' => ['period' => 'weekly', 'week' => $this->start->toDateString()],
            'monthly' => ['period' => 'monthly', 'month' => $this->start->format('Y-m')],
            default => ['period' => 'custom', 'start_date' => $this->start->toDateString(), 'end_date' => $this->endExclusive->subDay()->toDateString()],
        };
    }

    public function isCurrentBusinessDay(?CarbonImmutable $now = null): bool
    {
        return $this->mode === 'daily'
            && $this->start->toDateString() === ($now ?? CarbonImmutable::now($this->timezone))->setTimezone($this->timezone)->toDateString();
    }

    public function label(): string
    {
        return match ($this->mode) {
            'daily' => $this->start->translatedFormat('l, d M Y'),
            'weekly' => $this->start->format('d M').' – '.$this->endExclusive->subDay()->format('d M Y'),
            'monthly' => $this->start->locale(app()->getLocale())->translatedFormat('F Y'),
            default => $this->start->format('d M Y').' – '.$this->endExclusive->subDay()->format('d M Y'),
        };
    }

    private static function custom(array $input, string $timezone): self
    {
        if (empty($input['start_date']) || empty($input['end_date'])) {
            throw new InvalidArgumentException('A custom reporting period needs a start and end date.');
        }

        $start = CarbonImmutable::parse($input['start_date'], $timezone)->startOfDay();
        $end = CarbonImmutable::parse($input['end_date'], $timezone)->startOfDay();
        if ($end < $start) {
            throw new InvalidArgumentException('The end date must not precede the start date.');
        }

        return new self('custom', $start, $end->addDay(), $timezone);
    }

    private static function between(string $mode, CarbonImmutable $start, int $amount, string $unit, string $timezone): self
    {
        return new self($mode, $start, $start->add($amount, $unit), $timezone);
    }

    private function unit(): string
    {
        return match ($this->mode) {
            'daily' => 'day',
            'weekly' => 'week',
            default => 'month',
        };
    }
}
