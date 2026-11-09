<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    private const MAX_URLS = 50000;

    public function index(): Response
    {
        $services = Service::query()->publiclyVisible()->orderBy('id')->get(['slug', 'updated_at']);
        $staticUrls = $this->staticUrls();

        if ($services->count() + $staticUrls->count() <= self::MAX_URLS) {
            return $this->urlSet($staticUrls->concat($this->serviceUrls($services)));
        }

        $baseUrl = $this->baseUrl();
        $shards = $services->chunk(self::MAX_URLS);
        $xml = view('public.sitemap-index', [
            'sitemaps' => $shards->keys()->map(fn ($index) => $baseUrl.'/sitemap-services-'.($index + 1).'.xml'),
        ])->render();

        return $this->xmlResponse('<?xml version="1.0" encoding="UTF-8"?>'."\n".$xml);
    }

    public function services(int $page): Response
    {
        abort_if($page < 1, 404);

        $services = Service::query()->publiclyVisible()->orderBy('id')->get(['slug', 'updated_at']);
        abort_if($services->count() + $this->staticUrls()->count() <= self::MAX_URLS, 404);

        $chunk = $services->chunk(self::MAX_URLS)->get($page - 1);
        abort_if($chunk === null, 404);

        return $this->urlSet($this->serviceUrls($chunk));
    }

    private function staticUrls(): Collection
    {
        return collect([
            ['loc' => $this->baseUrl().'/', 'lastmod' => null],
            ['loc' => $this->baseUrl().'/services', 'lastmod' => null],
            ['loc' => $this->baseUrl().'/gallery', 'lastmod' => null],
            ['loc' => $this->baseUrl().'/about', 'lastmod' => null],
            ['loc' => $this->baseUrl().'/contact', 'lastmod' => null],
            ['loc' => $this->baseUrl().'/privacy-policy', 'lastmod' => null],
            ['loc' => $this->baseUrl().'/book-appointment', 'lastmod' => null],
        ]);
    }

    private function serviceUrls(Collection $services): Collection
    {
        return $services->map(fn (Service $service) => [
            'loc' => $this->baseUrl().'/services/'.$service->slug,
            'lastmod' => $service->updated_at?->toAtomString(),
        ]);
    }

    private function urlSet(Collection $urls): Response
    {
        $xml = view('public.sitemap', [
            'urls' => $urls->unique('loc')->filter(fn (array $url) => filled($url['loc']))->values(),
        ])->render();

        return $this->xmlResponse('<?xml version="1.0" encoding="UTF-8"?>'."\n".$xml);
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=86400, must-revalidate');
    }

    private function baseUrl(): string
    {
        return 'https://5star.sushako.in';
    }
}
