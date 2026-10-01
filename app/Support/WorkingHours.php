<?php

namespace App\Support;

use App\Models\SalonSetting;
use Illuminate\Support\Carbon;

class WorkingHours
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public static function schedule(?array $settings = null): array
    {
        $settings ??= SalonSetting::cached();
        $stored = json_decode((string) ($settings['weekly_working_hours'] ?? ''), true);

        return collect(self::DAYS)->mapWithKeys(function (string $day) use ($stored): array {
            $row = is_array($stored) ? ($stored[$day] ?? null) : null;
            $open = is_array($row) && filter_var($row['open'] ?? false, FILTER_VALIDATE_BOOLEAN) && self::validTime($row['opens'] ?? null) && self::validTime($row['closes'] ?? null);

            return [$day => [
                'open' => $open,
                'opens' => $open ? $row['opens'] : null,
                'closes' => $open ? $row['closes'] : null,
            ]];
        })->all();
    }

    public static function status(?Carbon $now = null, ?array $settings = null): ?bool
    {
        $now ??= now('Asia/Kolkata');
        $schedule = self::schedule($settings);
        $today = $now->format('l');
        $row = $schedule[$today] ?? null;

        $current = $now->format('H:i');
        if ($row && $row['open']) {
            if ($row['opens'] <= $row['closes'] && $current >= $row['opens'] && $current < $row['closes']) return true;
            if ($row['opens'] > $row['closes'] && ($current >= $row['opens'] || $current < $row['closes'])) return true;
        }

        $previous = $schedule[$now->copy()->subDay()->format('l')] ?? null;

        return (bool) ($previous && $previous['open'] && $previous['opens'] > $previous['closes'] && $current < $previous['closes']);
    }

    public static function label(string $time): string
    {
        return Carbon::createFromFormat('H:i', $time, 'Asia/Kolkata')->format('g:i A');
    }

    private static function validTime(mixed $time): bool
    {
        return is_string($time) && (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
    }
}
