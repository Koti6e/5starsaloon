<?php

namespace App\Services;

use App\Models\SalonSetting;

class AttendanceGeofence
{
    private const SALON_LATITUDE = 12.8821489;

    private const SALON_LONGITUDE = 80.0808760;

    private const APPROVED_RADIUS_METERS = 200.0;

    /** @return array{distance: float, radius: float, accuracy_limit: float, allowed: bool, reason: ?string} */
    public function evaluate(?float $latitude, ?float $longitude, ?float $accuracy): array
    {
        $settings = SalonSetting::cached();
        $salonLatitude = self::SALON_LATITUDE;
        $salonLongitude = self::SALON_LONGITUDE;
        $radius = self::APPROVED_RADIUS_METERS;
        $accuracyLimit = max(1, (float) ($settings['attendance_gps_accuracy_threshold_meters'] ?? 100));

        if ($latitude === null || $longitude === null) {
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
            $accuracy === null => 'GPS accuracy information is missing.',
            $accuracy > $accuracyLimit => 'GPS accuracy is too low. Move to an open area and retry.',
            $distance > $radius => 'Location is outside the configured salon attendance area.',
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
