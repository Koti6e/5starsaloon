<?php

namespace App\Services;

use App\Models\SalonSetting;

class AttendanceGeofence
{
    private const SALON_LATITUDE = 12.8821489;

    private const SALON_LONGITUDE = 80.0808760;

    private const APPROVED_RADIUS_METERS = 200.0;

    /** @return array{salon_latitude: float, salon_longitude: float, radius_meters: float, accuracy_limit_meters: float} */
    public function clientConfiguration(): array
    {
        $settings = SalonSetting::cached();

        return [
            'salon_latitude' => self::SALON_LATITUDE,
            'salon_longitude' => self::SALON_LONGITUDE,
            'radius_meters' => self::APPROVED_RADIUS_METERS,
            'accuracy_limit_meters' => max(1, (float) ($settings['attendance_gps_accuracy_threshold_meters'] ?? 100)),
        ];
    }

    /** @return array{distance: float, radius: float, accuracy_limit: float, allowed: bool, reason: ?string} */
    public function evaluate(?float $latitude, ?float $longitude, ?float $accuracy): array
    {
        $configuration = $this->clientConfiguration();
        $salonLatitude = $configuration['salon_latitude'];
        $salonLongitude = $configuration['salon_longitude'];
        $radius = $configuration['radius_meters'];
        $accuracyLimit = $configuration['accuracy_limit_meters'];

        if ($latitude === null || $longitude === null || ! is_finite($latitude) || ! is_finite($longitude)
            || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return ['distance' => INF, 'radius' => $radius, 'accuracy_limit' => $accuracyLimit, 'allowed' => false, 'reason' => 'Location permission or GPS coordinates are missing.'];
        }

        $earthRadiusMeters = 6_371_000.0;
        $lat1 = deg2rad($salonLatitude);
        $lat2 = deg2rad($latitude);
        $deltaLat = deg2rad($latitude - $salonLatitude);
        $deltaLon = deg2rad($longitude - $salonLongitude);
        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;
        $distance = 2 * $earthRadiusMeters * asin(min(1, sqrt($a)));
        $reason = match (true) {
            $accuracy === null || ! is_finite($accuracy) || $accuracy < 0 => 'GPS accuracy information is missing or invalid.',
            $accuracy > $accuracyLimit => 'GPS accuracy is '.number_format($accuracy, 1).' metres; the configured limit is '.number_format($accuracyLimit, 1).' metres. Keep location enabled and retry GPS.',
            $distance > $radius => 'Location is approximately '.number_format($distance, 1).' metres from the salon; attendance requires a location within '.number_format($radius, 0).' metres.',
            default => null,
        };

        return [
            'distance' => round($distance, 2),
            'radius' => $radius,
            'accuracy_limit' => $accuracyLimit,
            'allowed' => $reason === null,
            'reason' => $reason,
        ];
    }
}
