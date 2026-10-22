<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sendTemplate(string $to, array $parameters): array
    {
        $version = (string) config('services.whatsapp.api_version');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $accessToken = (string) config('services.whatsapp.access_token');
        $templateName = (string) config('services.whatsapp.invoice_template');
        $language = (string) config('services.whatsapp.invoice_template_language');

        if (! $version || ! $phoneNumberId || ! $accessToken || ! $templateName || ! $language) {
            return ['success' => false, 'error' => 'WhatsApp API credentials or invoice template configuration are missing.'];
        }

        if (! preg_match('/^v\d+\.\d+$/', $version) || ! preg_match('/^\d+$/', $phoneNumberId)) {
            return ['success' => false, 'error' => 'WhatsApp API configuration is invalid.'];
        }

        if (count($parameters) !== 4 || ! preg_match('/^91[6-9]\d{9}$/', $to)) {
            return ['success' => false, 'error' => 'Invoice recipient or template parameters are invalid.'];
        }

        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        try {
            $attempt = 0;
            $attemptCount = 0;
            do {
                $attemptCount++;
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->post($url, [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'template',
                        'template' => [
                            'name' => $templateName,
                            'language' => ['code' => $language],
                            'components' => [[
                                'type' => 'body',
                                'parameters' => array_map(fn ($value) => [
                                    'type' => 'text',
                                    'text' => (string) $value,
                                ], $parameters),
                            ]],
                        ],
                    ]);

                if ($response->status() !== 429 || $attempt >= 1) {
                    break;
                }

                $attempt++;
                usleep(500_000);
            } while (true);
        } catch (ConnectionException) {
            Log::warning('WhatsApp API connection failed before a response was received.');

            return ['success' => false, 'error' => 'WhatsApp API connection failed.'];
        }

        if ($response->successful()) {
            $messageId = data_get($response->json(), 'messages.0.id');

            if (is_string($messageId) && $messageId !== '') {
                return ['success' => true, 'message_id' => $messageId];
            }

            Log::warning('WhatsApp API accepted a request without returning a message ID.');

            return ['success' => false, 'error' => 'WhatsApp API response did not include a message ID.'];
        }

        $errorCode = data_get($response->json(), 'error.code');
        $errorSubcode = data_get($response->json(), 'error.error_subcode');
        $errorType = data_get($response->json(), 'error.type');
        Log::warning('WhatsApp API rejected an invoice request.', [
            'http_status' => $response->status(),
            'error_code' => is_numeric($errorCode) ? (int) $errorCode : null,
            'error_subcode' => is_numeric($errorSubcode) ? (int) $errorSubcode : null,
            'error_type' => is_string($errorType) ? mb_substr(preg_replace('/[^A-Za-z0-9_]/', '', $errorType), 0, 80) : null,
            'attempt_count' => $attemptCount,
        ]);

        $category = match (true) {
            $response->status() === 401 || $response->status() === 403 => 'authentication/permission error',
            $response->status() === 429 => 'rate limit',
            $response->status() >= 500 => 'Meta service error',
            default => 'invalid request or template parameters',
        };

        return [
            'success' => false,
            'error' => 'WhatsApp '.$category.' (HTTP '.$response->status().($errorCode ? ', code '.$errorCode : '').').',
        ];
    }
}
