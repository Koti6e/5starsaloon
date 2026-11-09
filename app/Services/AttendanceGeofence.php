<?php

namespace App\Services;

use App\Models\SalonSetting;
use Illuminate\Validation\ValidationException;

class AttendanceGeofence
{
    public const DEFAULT_LATITUDE = 12.8821489;
    public const DEFAULT_LONGITUDE = 80.0808760;
    public const DEFAULT_RADIUS = 200.0;

    /**
     * @return array{latitude: float, longitude: float, accuracy: float, distance: float, status: string}
     */
    public function verify(?string $latitude, ?string $longitude, ?string $accuracy): array
    {
        if ($latitude === null || $longitude === null || $accuracy === null || $latitude === '' || $longitude === '' || $accuracy === '') {
            throw ValidationException::withMessages(['location' => 'Location permission is required. Enable location access and try again.']);
        }

        if (! is_numeric($latitude) || ! is_numeric($longitude) || ! is_numeric($accuracy)) {
            throw ValidationException::withMessages(['location' => 'Your location could not be read. Please retry with location enabled.']);
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $accuracy = (float) $accuracy;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 || $accuracy < 0) {
            throw ValidationException::withMessages(['location' => 'Your location data is invalid. Please retry.']);
        }

        if ($accuracy > 100) {
            throw ValidationException::withMessages(['location' => 'GPS accuracy is too low ('.$this->formatMeters($accuracy).'). Move to an open area and retry.']);
        }

        $settings = SalonSetting::cached();
        $salonLatitude = (float) ($settings['attendance_latitude'] ?? $settings['latitude'] ?? self::DEFAULT_LATITUDE);
        $salonLongitude = (float) ($settings['attendance_longitude'] ?? $settings['longitude'] ?? self::DEFAULT_LONGITUDE);
        $radius = (float) ($settings['attendance_geofence_radius'] ?? self::DEFAULT_RADIUS);
        $distance = $this->distance($latitude, $longitude, $salonLatitude, $salonLongitude);

        if ($distance > $radius) {
            throw ValidationException::withMessages(['location' => 'You are '.$this->formatMeters($distance).' away from the salon. Attendance is permitted only within '.$this->formatMeters($radius).'.']);
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'distance' => $distance,
            'status' => $accuracy > 50 ? 'poor_accuracy_review' : 'verified',
        ];
    }

    public function distance(float $latitude, float $longitude, float $salonLatitude, float $salonLongitude): float
    {
        $earthRadius = 6_371_000;
        $latDelta = deg2rad($salonLatitude - $latitude);
        $longDelta = deg2rad($salonLongitude - $longitude);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($latitude)) * cos(deg2rad($salonLatitude)) * sin($longDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function formatMeters(float $meters): string
    {
        return $meters < 1000 ? round($meters).' metres' : round($meters / 1000, 2).' km';
    }
}
