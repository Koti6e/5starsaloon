<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SalonSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return self::cached()[$key] ?? $default;
    }

    /**
     * @return array<string, string|null>
     */
    public static function cached(): array
    {
        return Cache::rememberForever('salon_settings', fn () => self::query()
            ->pluck('value', 'key')
            ->all());
    }

    public static function putValue(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('salon_settings');
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::getValue($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function activePromotion(): ?array
    {
        $settings = self::cached();
        if (! filter_var($settings['promotion_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $today = now('Asia/Kolkata')->toDateString();
        $starts = $settings['promotion_start_date'] ?? null;
        $ends = $settings['promotion_end_date'] ?? null;

        if (($starts && $starts > $today) || ($ends && $ends < $today)) {
            return null;
        }

        if (blank($settings['promotion_title'] ?? null) || blank($settings['promotion_button_link'] ?? null)) {
            return null;
        }

        return $settings;
    }
}
