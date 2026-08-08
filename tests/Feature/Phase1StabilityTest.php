<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentNumberGenerator;
use App\Services\CustomerCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase1StabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_safely_when_appointments_table_is_unavailable(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Appointments table is unavailable when accessing Admin Dashboard metrics.');

        Schema::dropIfExists('appointment_services');
        Schema::dropIfExists('appointments');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('todayAppointments', 0)
            ->assertViewHas('pendingHomeVisits', 0);
    }

    public function test_staff_dashboard_renders_safely_when_appointments_table_is_unavailable(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Appointments table is unavailable when accessing Staff Dashboard metrics.');

        Schema::dropIfExists('appointment_services');
        Schema::dropIfExists('appointments');

        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertViewHas('assignedAppointments', 0);
    }

    public function test_customer_code_generator_handles_soft_deleted_records_concurrency_safe(): void
    {
        $year = now('Asia/Kolkata')->format('Y');

        $customer1 = Customer::factory()->create(['customer_code' => "CUS-{$year}-000001"]);
        $customer2 = Customer::factory()->create(['customer_code' => "CUS-{$year}-000002"]);
        $customer2->delete(); // soft delete

        $generator = new CustomerCodeGenerator;
        $nextCode = $generator->generate();

        $this->assertEquals("CUS-{$year}-000003", $nextCode);
    }

    public function test_appointment_number_generator_resets_monthly_and_formats_correctly(): void
    {
        $now = now('Asia/Kolkata');
        $period = $now->format('Y/m');

        $customer = Customer::factory()->create();

        Appointment::factory()->create([
            'booking_number' => "5star/App/{$period}/001",
            'customer_id' => $customer->id,
        ]);

        $generator = new AppointmentNumberGenerator;
        $nextNumber = $generator->generate();

        $this->assertEquals("5star/App/{$period}/002", $nextNumber);
    }

    public function test_public_appointment_booking_validation_errors_and_old_input_preservation(): void
    {
        $service = Service::query()->where('status', 'active')->first();
        if (! $service) {
            $service = Service::query()->create([
                'category_id' => ServiceCategory::query()->firstOrCreate([
                    'slug' => 'service-test-category',
                ], [
                    'name' => 'Service Test Category',
                    'is_active' => true,
                ])->id,
                'name' => 'Test Service',
                'slug' => 'test-service',
                'service_code' => 'SVC-TEST-SERVICE',
                'short_description' => 'Test service description',
                'price_type' => 'fixed',
                'price' => '250.00',
                'duration_minutes' => 30,
                'status' => 'active',
                'is_salon_service_available' => true,
                'is_home_service_available' => true,
            ]);
        }

        $response = $this->post(route('appointments.store'), [
            'appointment_type' => 'home_service',
            'service_slug' => $service->slug,
            'appointment_date' => today()->addDay()->toDateString(),
            'appointment_time' => '11:00',
            'customer_name' => 'John Doe',
            'mobile' => '123', // invalid mobile
            'address' => '123 Test Street, Mumbai',
            'consent' => '1',
        ]);

        $response->assertSessionHasErrors(['mobile']);
        $response->assertSessionHasInput('customer_name', 'John Doe');
        $response->assertSessionHasInput('appointment_type', 'home_service');
        $response->assertSessionHasInput('service_slug', $service->slug);
        $response->assertSessionHasInput('address', '123 Test Street, Mumbai');
    }

    public function test_valid_salon_visit_booking_creates_appointment_and_redirects_to_confirmation(): void
    {
        $service = Service::query()->where('status', 'active')->firstOrFail();

        $payload = [
            'appointment_type' => 'salon_visit',
            'service_slug' => $service->slug,
            'appointment_date' => today()->addDay()->toDateString(),
            'appointment_time' => '14:00',
            'customer_name' => 'Vikram Seth',
            'mobile' => '9876543210',
            'email' => 'vikram@example.com',
            'notes' => 'Prefer senior hair stylist.',
            'consent' => '1',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $appointment = Appointment::query()->where('customer_notes', 'Prefer senior hair stylist.')->firstOrFail();

        $response->assertRedirect(route('appointments.confirmed', ['token' => $appointment->confirmation_token]));
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'appointment_type' => 'salon_visit',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('appointment_services', [
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_valid_home_service_booking_requires_address_and_creates_appointment(): void
    {
        $service = Service::query()->where('status', 'active')->where('is_home_service_available', true)->first();
        if (! $service) {
            $service = Service::query()->where('status', 'active')->firstOrFail();
            $service->update(['is_home_service_available' => true]);
        }

        $payload = [
            'appointment_type' => 'home_service',
            'service_slug' => $service->slug,
            'appointment_date' => today()->addDay()->toDateString(),
            'appointment_time' => '11:00',
            'customer_name' => 'Anita Roy',
            'mobile' => '9812345678',
            'address' => 'Flat 402, Royal Residency, Bandra West, Mumbai 400050',
            'consent' => '1',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $appointment = Appointment::query()->where('address_line_1', 'Flat 402, Royal Residency, Bandra West, Mumbai 400050')->firstOrFail();

        $response->assertRedirect(route('appointments.confirmed', ['token' => $appointment->confirmation_token]));
    }

    public function test_confirmation_page_displays_data_and_whatsapp_intent(): void
    {
        $service = Service::query()->where('status', 'active')->firstOrFail();
        $customer = Customer::factory()->create(['name' => 'Sunil Kumar', 'mobile' => '9988776655']);

        $appointment = Appointment::factory()->create([
            'booking_number' => '5star/App/2026/08/001',
            'confirmation_token' => 'test-secure-token-12345',
            'customer_id' => $customer->id,
            'appointment_type' => 'salon_visit',
            'date' => today()->addDay()->toDateString(),
            'start_time' => '15:00',
            'status' => 'pending',
        ]);

        $appointment->appointmentServices()->create([
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'unit_price' => $service->price ?? 500,
            'duration_minutes' => 30,
        ]);

        $response = $this->get(route('appointments.confirmed', ['token' => 'test-secure-token-12345']));

        $response->assertOk()
            ->assertSee('Appointment Confirmed!')
            ->assertSee('5star/App/2026/08/001')
            ->assertSee('Sunil Kumar')
            ->assertSee($service->name)
            ->assertSee('wa.me');
    }

    public function test_newly_booked_appointment_increments_admin_dashboard_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $customer = Customer::factory()->create();

        Appointment::factory()->create([
            'date' => now('Asia/Kolkata')->toDateString(),
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('todayAppointments', 1);
    }
}
