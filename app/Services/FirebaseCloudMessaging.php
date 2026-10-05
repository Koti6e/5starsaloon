<?php

namespace App\Services;

use App\Models\FcmDeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FirebaseCloudMessaging
{
    private ?string $cachedAccessToken = null;

    private int $cachedAccessTokenExpiresAt = 0;

    private ?string $cachedCredentialFingerprint = null;

    public function send(FcmDeviceToken $deviceToken, string $title, string $body, string $url, array $data = []): bool
    {
        $result = $this->sendToToken($deviceToken->token, $title, $body, $url, $data);

        if ($result['sent']) {
            $deviceToken->forceFill(['last_seen_at' => now(), 'revoked_at' => null])->save();
        } elseif ($result['invalid']) {
            $deviceToken->forceFill(['revoked_at' => now()])->save();
        }

        return $result['sent'];
    }

    /**
     * @return array{sent: bool, invalid: bool}
     */
    public function sendToToken(string $token, string $title, string $body, string $url, array $data = []): array
    {
        $credentials = $this->credentials();
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);
        if (! $credentials || ! $projectId) {
            Log::info('FCM notification skipped because Firebase credentials are not configured.');

            return ['sent' => false, 'invalid' => false];
        }

        try {
            $accessToken = $this->accessToken($credentials);
            $response = Http::withToken($accessToken)
                ->timeout(8)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'data' => collect($data)
                            ->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))
                            ->put('title', $title)
                            ->put('body', $body)
                            ->put('url', $url)
                            ->all(),
                    ],
                ]);

            if ($response->successful()) {
                return ['sent' => true, 'invalid' => false];
            }

            Log::warning('FCM notification failed.', ['status' => $response->status(), 'body' => Str::limit($response->body(), 500)]);

            return [
                'sent' => false,
                'invalid' => in_array($response->status(), [404, 410], true)
                    || data_get($response->json(), 'error.details.0.errorCode') === 'UNREGISTERED',
            ];
        } catch (\Throwable $exception) {
            Log::warning('FCM notification could not be delivered.', ['exception' => $exception]);

            return ['sent' => false, 'invalid' => false];
        }
    }

    public function isConfigured(): bool
    {
        $credentials = $this->credentials();
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);

        return filled($projectId)
            && filled($credentials['client_email'] ?? null)
            && filled($credentials['private_key'] ?? null);
    }

    public function isWebPushConfigured(): bool
    {
        return $this->isConfigured()
            && filled(config('services.firebase.web_api_key'))
            && filled(config('services.firebase.auth_domain'))
            && filled(config('services.firebase.messaging_sender_id'))
            && filled(config('services.firebase.app_id'))
            && filled(config('services.firebase.vapid_key'));
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
        $credentialFingerprint = hash('sha256', $credentials['client_email'].'|'.$credentials['private_key']);
        if (
            $this->cachedAccessToken
            && $this->cachedCredentialFingerprint === $credentialFingerprint
            && $this->cachedAccessTokenExpiresAt > time()
        ) {
            return $this->cachedAccessToken;
        }

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

        $accessToken = (string) $response->json('access_token');
        if (blank($accessToken)) {
            throw new \RuntimeException('Firebase OAuth did not return an access token.');
        }

        $this->cachedAccessToken = $accessToken;
        $this->cachedCredentialFingerprint = $credentialFingerprint;
        $this->cachedAccessTokenExpiresAt = time() + max(60, min(3500, (int) $response->json('expires_in', 3600) - 60));

        return $this->cachedAccessToken;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
