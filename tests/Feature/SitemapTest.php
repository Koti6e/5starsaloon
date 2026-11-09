<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_is_valid_and_lists_public_canonical_pages_and_services_only(): void
    {
        $category = ServiceCategory::query()->create(['name' => 'Haircuts', 'slug' => 'haircuts', 'is_active' => true]);
        $active = $this->service($category, 'Haircut', 'active');
        $inactive = $this->service($category, 'Archived Service', 'inactive');
        $inactiveCategory = ServiceCategory::query()->create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
        $hidden = $this->service($inactiveCategory, 'Hidden Service', 'active');

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($response->getContent()));

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $locations = array_map(fn ($node) => $node->textContent, iterator_to_array($xpath->query('//sm:url/sm:loc')));

        $this->assertContains('https://5star.sushako.in/', $locations);
        $this->assertContains('https://5star.sushako.in/services', $locations);
        $this->assertContains('https://5star.sushako.in/book-appointment', $locations);
        $this->assertContains('https://5star.sushako.in/services/'.$active->slug, $locations);
        $this->assertNotContains('https://5star.sushako.in/services/'.$inactive->slug, $locations);
        $this->assertNotContains('https://5star.sushako.in/services/'.$hidden->slug, $locations);
        $this->assertSame(count($locations), count(array_unique($locations)));
        $this->assertSame(0, $xpath->query('//sm:url[not(sm:loc) or normalize-space(sm:loc)=""]')->length);
        $this->assertSame(0, $xpath->query('//sm:url[sm:loc[contains(., "/admin") or contains(., "/staff") or contains(., "/dashboard")]]')->length);
        $this->assertSame(0, $xpath->query('//sm:changefreq | //sm:priority')->length);
    }

    public function test_robots_txt_allows_public_pages_and_points_to_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('User-agent: *', false)
            ->assertSee('Allow: /', false)
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Sitemap: https://5star.sushako.in/sitemap.xml', false);
    }

    private function service(ServiceCategory $category, string $name, string $status): Service
    {
        return Service::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug().'-sitemap-test',
            'service_code' => 'SVC-'.str($name)->slug('-')->upper().'-SITEMAP',
            'short_description' => $name.' service',
            'price_type' => 'fixed',
            'price' => '120.00',
            'duration_minutes' => 30,
            'status' => $status,
            'is_salon_service_available' => true,
        ]);
    }
}
