<?php

namespace App\Http\Requests\Auth;

use App\Models\AttendanceVerificationAttempt;
use App\Models\User;
use App\Services\AttendanceGeofence;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'gps_timestamp' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $username = $this->string('username')->lower()->trim()->toString();
        $staffCandidate = User::query()
            ->where('username', $username)
            ->where('role', 'staff')
            ->first();
        $gpsContext = null;

        if ($staffCandidate?->requiresLoginGeofence()) {
            $coordinates = $this->coordinates();
            $location = app(AttendanceGeofence::class)->evaluate(
                $coordinates['latitude'],
                $coordinates['longitude'],
                $coordinates['accuracy'],
            );
            $freshnessFailure = $this->gpsFreshnessFailure($coordinates['timestamp']);
            if ($freshnessFailure !== null && $coordinates['latitude'] !== null && $coordinates['longitude'] !== null) {
                $location['allowed'] = false;
                $location['reason'] = $freshnessFailure;
            }
            $gpsContext = compact('staffCandidate', 'coordinates', 'location');

            if (! $location['allowed']) {
                $this->recordGpsAttempt($staffCandidate, $coordinates, $location, 'rejected', $location['reason'] ?? 'GPS verification failed.');
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'username' => $location['reason'] ?? 'GPS verification failed. Retry GPS and try again.',
                ]);
            }
        }

        $credentials = [
            'username' => $username,
            'password' => $this->string('password')->toString(),
            'status' => 'active',
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            if ($gpsContext) {
                $this->recordGpsAttempt(
                    $gpsContext['staffCandidate'],
                    $gpsContext['coordinates'],
                    $gpsContext['location'],
                    'rejected',
                    'GPS location passed; account credentials were rejected.',
                );
            }

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        if ($gpsContext) {
            $this->recordGpsAttempt(
                $gpsContext['staffCandidate'],
                $gpsContext['coordinates'],
                $gpsContext['location'],
                'verified',
                'GPS location and account credentials passed.',
            );
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** @return array{latitude: ?float, longitude: ?float, accuracy: ?float, timestamp: ?float} */
    private function coordinates(): array
    {
        $latitude = $this->numberOrNull($this->input('latitude'));
        $longitude = $this->numberOrNull($this->input('longitude'));
        $accuracy = $this->numberOrNull($this->input('gps_accuracy_meters'));
        $timestamp = $this->numberOrNull($this->input('gps_timestamp'));

        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            $latitude = null;
        }
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            $longitude = null;
        }
        if ($accuracy !== null && ($accuracy < 0 || $accuracy > 100000)) {
            $accuracy = null;
        }

        return ['latitude' => $latitude, 'longitude' => $longitude, 'accuracy' => $accuracy, 'timestamp' => $timestamp];
    }

    private function gpsFreshnessFailure(?float $timestamp): ?string
    {
        if ($timestamp === null) {
            return 'The GPS fix has no timestamp. Tap Retry GPS to get a fresh location.';
        }

        $ageMilliseconds = now()->valueOf() - $timestamp;
        if ($ageMilliseconds > 120_000) {
            return 'The GPS fix is more than two minutes old. Tap Retry GPS to get a fresh location.';
        }
        if ($ageMilliseconds < -30_000) {
            return 'The GPS fix timestamp is invalid. Check your device clock and retry GPS.';
        }

        return null;
    }

    private function numberOrNull(mixed $value): ?float
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return null;
        }

        return (float) $value;
    }

    /** @param array{latitude: ?float, longitude: ?float, accuracy: ?float} $coordinates
     * @param  array{distance: float, radius: float, accuracy_limit: float, allowed: bool, reason: ?string}  $location
     */
    private function recordGpsAttempt(User $staff, array $coordinates, array $location, string $decision, string $reason): void
    {
        AttendanceVerificationAttempt::query()->create([
            'staff_id' => $staff->id,
            'attempt_type' => 'login_gps',
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'gps_accuracy_meters' => $coordinates['accuracy'],
            'gps_position_at' => $coordinates['timestamp'] !== null
                ? \Illuminate\Support\Carbon::createFromTimestampMs((int) round($coordinates['timestamp']), config('app.timezone'))
                : null,
            'distance_from_salon_meters' => is_finite($location['distance']) ? $location['distance'] : null,
            'decision' => $decision,
            'reason' => $reason,
            'attempted_at' => now('Asia/Kolkata'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
