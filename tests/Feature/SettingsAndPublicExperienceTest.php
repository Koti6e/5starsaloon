<?php

namespace Tests\Feature;

use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAndPublicExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_theme_defaults_to_light_and_uses_icon_toggle(): void
    {
        SalonSetting::putValue('default_theme', 'light');

        $this->get('/')
            ->assertOk()
            ->assertSee('dataset.defaultTheme', false)
            ->assertSee('Switch to dark mode')
            ->assertDontSee('Dark Theme');
    }

    public function test_admin_can_update_settings_and_staff_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($staff)->put(route('admin.settings.update'), [])->assertForbidden();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'salon_name' => '5 Star New Look Salon',
            'default_theme' => 'light',
            'invoice_prefix' => '5STAR',
            'whatsapp_number' => '9876543210',
            'whatsapp_floater_enabled' => '1',
        ])->assertRedirect();

        $this->assertSame('9876543210', SalonSetting::getValue('whatsapp_number'));
        $this->assertTrue(SalonSetting::bool('whatsapp_floater_enabled'));
    }

    public function test_whatsapp_floater_renders_when_enabled_and_hides_when_disabled(): void
    {
        SalonSetting::putValue('whatsapp_number', '9876543210');
        SalonSetting::putValue('whatsapp_floater_enabled', '1');

        $this->get('/')->assertOk()->assertSee('wa.me/919876543210', false);

        SalonSetting::putValue('whatsapp_floater_enabled', '0');

        $this->get('/')->assertOk()->assertDontSee('wa.me/');
    }

    public function test_service_whatsapp_message_contains_service_name(): void
    {
        SalonSetting::putValue('whatsapp_number', '9876543210');
        SalonSetting::putValue('whatsapp_floater_enabled', '1');
        $service = $this->service('Haircut');

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee(rawurlencode('Haircut'), false);
    }

    public function test_promotion_respects_dates(): void
    {
        SalonSetting::putValue('promotion_enabled', '1');
        SalonSetting::putValue('promotion_title', 'Haircut + Beard Trim');
        SalonSetting::putValue('promotion_offer_price', '₹199');
        SalonSetting::putValue('promotion_button_text', 'Book Now');
        SalonSetting::putValue('promotion_button_link', '/book-appointment');
        SalonSetting::putValue('promotion_start_date', now('Asia/Kolkata')->subDay()->toDateString());
        SalonSetting::putValue('promotion_end_date', now('Asia/Kolkata')->addDay()->toDateString());

        $this->get('/')->assertOk()->assertSee('Today’s Special')->assertSee('Haircut + Beard Trim');

        SalonSetting::putValue('promotion_end_date', now('Asia/Kolkata')->subDay()->toDateString());

        $this->get('/')->assertOk()->assertDontSee('Haircut + Beard Trim');
    }

    private function service(string $name): Service
    {
        if ($service = Service::query()->where('slug', str($name)->slug())->first()) {
            return $service;
        }

        $category = ServiceCategory::query()->firstOrCreate([
            'slug' => 'haircuts-grooming',
        ], [
            'name' => 'Haircuts & Grooming',
            'is_active' => true,
        ]);

        return Service::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'service_code' => 'SVC-'.str($name)->slug('-')->upper(),
            'short_description' => 'Precision salon service',
            'price_type' => 'fixed',
            'price' => '120.00',
            'duration_minutes' => 30,
            'status' => 'active',
            'is_salon_service_available' => true,
        ]);
    }
}
