<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\SalonClosedDate;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OnlineAppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Carbon::setTestNow(Carbon::parse('2026-10-05 17:30:00', 'Asia/Kolkata'));
        config(['app.timezone' => 'Asia/Kolkata']);
        $hours = [];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $hours[$day] = ['open' => true, 'opens' => '09:00', 'closes' => '21:00'];
        }
        $hours['Tuesday']['open'] = false;
        SalonSetting::putValue('weekly_working_hours', json_encode($hours));
        SalonSetting::putValue('appointment_slot_duration', '30');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_availability_offers_only_dates_with_valid_slots_and_respects_holidays(): void
    {
        $service = $this->service(90);
        SalonClosedDate::query()->create(['date' => '2026-10-07', 'name' => 'Holiday', 'is_active' => true]);

        $response = $this->getJson(route('appointments.availability', ['service_slug' => $service->slug]));
        $response->assertOk()->assertJsonMissing(['value' => '2026-10-06']);
        $dates = collect($response->json('dates'))->pluck('value');
        $this->assertFalse($dates->contains('2026-10-06'));
        $this->assertFalse($dates->contains('2026-10-07'));
        $this->assertTrue($dates->contains('2026-10-05'));
    }

    public function test_today_slots_obey_lead_time_open_close_duration_and_overlaps(): void
    {
        $service = $this->service(90);
        $response = $this->getJson(route('appointments.availability', ['service_slug' => $service->slug, 'date' => '2026-10-05']))->assertOk();
        $times = collect($response->json('slots'))->pluck('value');
        $this->assertFalse($times->contains('18:00'));
        $this->assertTrue($times->contains('18:30'), 'Exactly one hour ahead remains bookable.');
        $this->assertTrue($times->contains('19:00'));
        $this->assertTrue($times->contains('19:30'));
        $this->assertFalse($times->contains('20:00'));

        Appointment::factory()->create([
            'date' => '2026-10-05', 'start_time' => '18:30:00', 'estimated_end_time' => '20:00:00', 'status' => 'confirmed',
        ]);
        $updated = $this->getJson(route('appointments.availability', ['service_slug' => $service->slug, 'date' => '2026-10-05']))->assertOk();
        $this->assertFalse(collect($updated->json('slots'))->pluck('value')->contains('19:00'));
        $this->book($service, '2026-10-05', '19:00')->assertSessionHasErrors('appointment_time');
        $this->book($service, '2026-10-05', '18:00')->assertSessionHasErrors('appointment_time');
        $this->book($service, '2026-10-05', '20:00')->assertSessionHasErrors('appointment_time');
    }

    public function test_future_slots_start_at_opening_and_respect_duration_and_closing(): void
    {
        $service = $this->service(90);
        $response = $this->getJson(route('appointments.availability', ['service_slug' => $service->slug, 'date' => '2026-10-08']))->assertOk();
        $times = collect($response->json('slots'))->pluck('value');
        $this->assertTrue($times->contains('09:00'));
        $this->assertTrue($times->contains('19:30'));
        $this->assertFalse($times->contains('20:00'));
        $this->book($service, '2026-10-08', '08:30')->assertSessionHasErrors('appointment_time');
        $this->book($service, '2026-10-08', '21:00')->assertSessionHasErrors('appointment_time');
    }

    public function test_exactly_one_hour_ahead_is_valid_and_online_discount_is_saved(): void
    {
        $service = $this->service(30, '500.00');
        $response = $this->book($service, '2026-10-05', '18:30', ['discount' => 0, 'total' => 1, 'subtotal' => 1]);
        $appointment = Appointment::query()->firstOrFail();
        $response->assertRedirect(route('appointments.confirmed', ['token' => $appointment->confirmation_token]));
        $this->assertSame('ONLINE_BOOKING', $appointment->booking_source);
        $this->assertSame('50.00', $appointment->discount);
        $this->assertSame('450.00', $appointment->total);
        $this->get(route('appointments.confirmed', $appointment->confirmation_token))
            ->assertOk()->assertSee('Online Booking Discount (10%)')->assertSee('₹450');
    }

    public function test_closed_day_and_closed_date_are_rejected_by_booking_submission(): void
    {
        $service = $this->service();
        SalonClosedDate::query()->create(['date' => '2026-10-07', 'name' => 'Holiday', 'is_active' => true]);
        $this->book($service, '2026-10-06', '10:00')->assertSessionHasErrors('appointment_date');
        $this->book($service, '2026-10-07', '10:00')->assertSessionHasErrors('appointment_date');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_staff_billing_of_online_appointment_uses_saved_discount_once(): void
    {
        $service = $this->service(30, '500.00');
        $this->book($service, '2026-10-08', '10:00');
        $appointment = Appointment::query()->firstOrFail();
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'appointment_id' => $appointment->id,
            'customer_mobile' => $appointment->customer->mobile,
            'customer_name' => $appointment->customer->name,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'discount_amount' => '100.00',
            'payment_method' => 'cash',
            'idempotency_key' => 'online-appt-bill-test',
        ])->assertSessionHasNoErrors();

        $bill = $appointment->bills()->firstOrFail();
        $this->assertSame('50.00', $bill->discount_amount);
        $this->assertSame('450.00', $bill->grand_total);
        $this->actingAs($staff)->get(route('staff.billing.show', $bill))
            ->assertOk()->assertSee('Online Booking Discount (10%)')->assertSee('₹450');
        $this->actingAs($staff)->get(route('staff.billing.pdf', $bill))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_staff_booking_source_does_not_receive_online_discount(): void
    {
        $service = $this->service(30, '500.00');
        $appointment = Appointment::factory()->create([
            'booking_source' => 'STAFF_BOOKING', 'subtotal' => '500.00', 'discount' => '0.00', 'total' => '500.00',
        ]);
        $appointment->customer()->update(['name' => 'Staff Customer']);
        $appointment->appointmentServices()->create([
            'service_id' => $service->id, 'service_name_snapshot' => $service->name, 'unit_price' => '500.00', 'duration_minutes' => 30,
        ]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'appointment_id' => $appointment->id,
            'customer_mobile' => $appointment->customer->mobile,
            'customer_name' => $appointment->customer->name,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'staff-appt-bill-no-discount',
        ])->assertSessionHasNoErrors();
        $bill = $appointment->bills()->firstOrFail();
        $this->assertSame('0.00', $bill->discount_amount);
        $this->assertSame('500.00', $bill->grand_total);
    }

    private function book(Service $service, string $date, string $time, array $tampering = [])
    {
        return $this->post(route('appointments.store'), [
            'appointment_type' => 'salon_visit',
            'service_slug' => $service->slug,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'customer_name' => 'Test Customer',
            'mobile' => '9876543210',
            'consent' => '1',
            ...$tampering,
        ]);
    }

    private function service(int $duration = 30, string $price = '500.00'): Service
    {
        $category = ServiceCategory::query()->create(['name' => 'Test', 'slug' => uniqid('test-'), 'is_active' => true]);
        return Service::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Service',
            'slug' => uniqid('test-service-'),
            'service_code' => strtoupper(uniqid('TEST-')),
            'short_description' => 'Test service',
            'price_type' => 'fixed',
            'price' => $price,
            'duration_minutes' => $duration,
            'status' => 'active',
            'is_salon_service_available' => true,
        ]);
    }
}
