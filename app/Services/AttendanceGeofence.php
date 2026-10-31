<?php

namespace App\Services;

use App\Models\SalonSetting;

class AttendanceGeofence
{
    /** @return array{distance: float, radius: float, accuracy_limit: float, allowed: bool, reason: ?string} */
    public function evaluate(?float $latitude, ?float $longitude, ?float $accuracy): array
    {
        $settings = SalonSetting::cached();
        $salonLatitude = (float) ($settings['latitude'] ?? 12.8821489);
        $salonLongitude = (float) ($settings['longitude'] ?? 80.0808760);
        $radius = max(1, (float) ($settings['attendance_geofence_radius_meters'] ?? 200));
        $accuracyLimit = max(1, (float) ($settings['attendance_gps_accuracy_threshold_meters'] ?? 100));

        if ($latitude === null || $longitude === null || $accuracy === null) {
            return ['distance' => INF, 'radius' => $radius, 'accuracy_limit' => $accuracyLimit, 'allowed' => false, 'reason' => 'Location permission or GPS coordinates are missing.'];
        }

        if ($accuracy > $accuracyLimit) {
            return ['distance' => INF, 'radius' => $radius, 'accuracy_limit' => $accuracyLimit, 'allowed' => false, 'reason' => 'GPS accuracy is too low. Move to an open area and retry.'];
        }

        $earthRadiusMeters = 6_371_000.0;
        $lat1 = deg2rad($salonLatitude);
        $lat2 = deg2rad($latitude);
        $deltaLat = deg2rad($latitude - $salonLatitude);
        $deltaLon = deg2rad($longitude - $salonLongitude);
        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;
        $distance = 2 * $earthRadiusMeters * asin(min(1, sqrt($a)));

        return [
            'distance' => round($distance, 2),
            'radius' => $radius,
            'accuracy_limit' => $accuracyLimit,
            'allowed' => $distance <= $radius,
            'reason' => $distance <= $radius ? null : 'Location is outside the configured salon attendance area.',
        ];
    }
}
