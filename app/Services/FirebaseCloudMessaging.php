<?php

namespace App\Services;

use App\Models\FcmDeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FirebaseCloudMessaging
{
    public function send(FcmDeviceToken $deviceToken, string $title, string $body, string $url, array $data = []): bool
    {
        $credentials = $this->credentials();
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);
        if (! $credentials || ! $projectId) {
            Log::info('FCM notification skipped because Firebase credentials are not configured.');

            return false;
        }

        try {
            $accessToken = $this->accessToken($credentials);
            $response = Http::withToken($accessToken)
                ->timeout(8)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $deviceToken->token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'webpush' => [
                            'fcm_options' => ['link' => $url],
                            'notification' => ['icon' => asset('images/brand/logo-small.webp')],
                        ],
                        'data' => collect($data)
                            ->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))
                            ->put('url', $url)
                            ->all(),
                    ],
                ]);

            if ($response->successful()) {
                $deviceToken->forceFill(['last_seen_at' => now(), 'revoked_at' => null])->save();

                return true;
            }

            if (in_array($response->status(), [400, 404, 410], true)) {
                $deviceToken->forceFill(['revoked_at' => now()])->save();
            }

            Log::warning('FCM notification failed.', ['status' => $response->status(), 'body' => Str::limit($response->body(), 500)]);
        } catch (\Throwable $exception) {
            Log::warning('FCM notification could not be delivered.', ['exception' => $exception]);
        }

        return false;
    }

    private function credentials(): ?array
    {
        $json = config('services.firebase.credentials_json');
        if (filled($json)) {
            return json_decode((string) $json, true) ?: null;
        }

        $path = config('services.firebase.credentials_path');
        if (filled($path) && is_readable($path)) {
            return json_decode(file_get_contents($path) ?: '', true) ?: null;
        }

        return null;
    }

    private function accessToken(array $credentials): string
    {
        $now = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']) ?: '');
        $claims = $this->base64Url(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]) ?: '');

        openssl_sign($header.'.'.$claims, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $header.'.'.$claims.'.'.$this->base64Url($signature);

        $response = Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ])->throw();

        return (string) $response->json('access_token');
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
