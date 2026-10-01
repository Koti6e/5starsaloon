<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSearchConsoleService
{
    private ?string $accessToken = null;

    public function submitSitemap(): void
    {
        $site = (string) config('services.search_console.site_url');
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';
        Http::withToken($this->token())->send('PUT', $this->endpoint('/sitemaps/'.rawurlencode($sitemap)))->throw();
    }

    public function performance(string $startDate, string $endDate): array
    {
        $site = (string) config('services.search_console.site_url');

        return Http::withToken($this->token())
            ->post('https://searchconsole.googleapis.com/webmasters/v3/sites/'.rawurlencode($site).'/searchAnalytics/query', [
                'startDate' => $startDate, 'endDate' => $endDate,
                'dimensions' => ['date', 'query', 'page'], 'rowLimit' => 250,
            ])->throw()->json('rows', []);
    }

    public function inspect(string $url): array
    {
        return Http::withToken($this->token())
            ->post('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
                'inspectionUrl' => $url, 'siteUrl' => config('services.search_console.site_url'), 'languageCode' => 'en-US',
            ])->throw()->json('inspectionResult', []);
    }

    private function endpoint(string $suffix): string
    {
        return 'https://searchconsole.googleapis.com/webmasters/v3/sites/'.rawurlencode((string) config('services.search_console.site_url')).$suffix;
    }

    private function token(): string
    {
        if ($this->accessToken) return $this->accessToken;
        $id = config('services.search_console.client_id');
        $secret = config('services.search_console.client_secret');
        $refresh = config('services.search_console.refresh_token');
        if (! $id || ! $secret || ! $refresh) {
            throw new RuntimeException('Configure the Google Search Console OAuth client ID, client secret, and authorized refresh token in deployment secrets.');
        }

        $this->accessToken = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $id, 'client_secret' => $secret, 'refresh_token' => $refresh,
            'grant_type' => 'refresh_token',
        ])->throw()->json('access_token');

        if (! $this->accessToken) throw new RuntimeException('Google OAuth did not return an access token.');

        return $this->accessToken;
    }
}
