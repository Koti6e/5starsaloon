<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Customer;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_staff_profile_reports_period_bounded_work_and_attendance(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create([
            'role' => 'staff', 'status' => 'active', 'must_change_password' => false,
            'employee_code' => 'EMP-17', 'specialization' => 'Stylist', 'joining_date' => '2022-04-12',
        ]);
        $customer = Customer::factory()->create(['name' => 'Profile Customer']);
        $bill = Bill::query()->create([
            'invoice_number' => 'PROFILE-2026-001', 'customer_id' => $customer->id,
            'billed_by' => $staff->id, 'created_by' => $admin->id, 'subtotal' => '1000.00',
            'discount_amount' => '100.00', 'home_visit_charge' => '0.00', 'grand_total' => '900.00',
            'payment_status' => 'paid', 'status' => 'completed', 'idempotency_key' => 'staff-profile-001',
            'billed_at' => '2026-10-31 23:50:00',
        ]);
        BillItem::query()->create([
            'bill_id' => $bill->id, 'service_performed_by' => $staff->id,
            'service_name_snapshot' => 'Hair styling', 'service_code_snapshot' => 'HAIR',
            'category_name_snapshot' => 'Hair', 'quantity' => 2, 'unit_price' => '500.00',
            'line_total' => '1000.00', 'price_was_confirmed' => true,
        ]);
        StaffAttendance::query()->create([
            'staff_id' => $staff->id, 'attendance_date' => '2026-10-31', 'status' => 'late',
            'check_in_time' => '20:00:00', 'check_out_time' => '01:00:00', 'source' => 'manual_admin',
        ]);
        Appointment::factory()->create([
            'customer_id' => $customer->id, 'assigned_staff_id' => $staff->id,
            'date' => '2026-10-15', 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.show', [$staff, 'period' => 'monthly', 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('EMP-17')
            ->assertSee('Stylist')
            ->assertSee('Billed sales')
            ->assertSee('₹900.00')
            ->assertSee('Hair styling')
            ->assertSee('5h 0m')
            ->assertSee('Profile Customer');

        self::assertSame(1, (int) $response->viewData('billSummary')->bill_count);
        self::assertSame(1, $response->viewData('serviceRows')->count());
        self::assertSame(1, $response->viewData('attendanceCounts')->get('late'));
        self::assertCount(1, $response->viewData('appointments'));
    }

    public function test_staff_cannot_open_admin_staff_profile_and_non_staff_users_are_not_profiles(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $secondStaff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($staff)->get(route('admin.staff.show', $secondStaff))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.staff.show', $admin))->assertNotFound();
    }
}
