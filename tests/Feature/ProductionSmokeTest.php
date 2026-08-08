<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_workflows_render_and_submit(): void
    {
        $service = Service::query()->where('slug', 'haircut')->firstOrFail();

        $this->get(route('home'))->assertOk()->assertSee('5 Star New Look Salon');
        $this->get(route('services.index'))->assertOk()->assertSee('Haircut');
        $this->get(route('services.index', ['search' => 'Hair']))->assertOk()->assertSee('Haircut');
        $this->get(route('services.show', $service))->assertOk()->assertSee($service->name);
        $this->get(route('gallery'))->assertOk();
        $this->get(route('about'))->assertOk();
        $this->get(route('contact'))->assertOk()->assertSee('Send Enquiry');
        $this->get(route('appointments.book'))->assertOk()->assertSee('Book Appointment');

        $this->post(route('contact.store'), [
            'name' => 'QA Customer',
            'mobile' => '9876543210',
            'email' => 'qa@example.com',
            'subject' => 'Appointment request',
            'message' => 'Appointment request for Haircut',
            'consent' => '1',
            'website' => '',
        ])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_admin_and_staff_major_workflows_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        foreach ([
            route('admin.dashboard'),
            route('admin.billing.create'),
            route('admin.customers.index'),
            route('admin.customers.create'),
            route('admin.services.index'),
            route('admin.services.create'),
            route('admin.attendance.index'),
            route('admin.settings.edit'),
            route('admin.staff.index'),
            route('admin.staff.create'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        foreach ([
            route('staff.dashboard'),
            route('staff.billing.create'),
        ] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }
    }
}
