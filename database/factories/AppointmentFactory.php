<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('tomorrow', '+1 month');
        $startTime = $this->faker->randomElement(['10:00', '11:00', '12:00', '14:00', '15:00']);

        return [
            'booking_number' => '5star/App/'.now('Asia/Kolkata')->format('Y/m').'/'.str_pad((string) $this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'confirmation_token' => Str::random(40),
            'customer_id' => Customer::factory(),
            'appointment_type' => 'salon_visit',
            'date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'estimated_end_time' => now('Asia/Kolkata')->parse($startTime)->addMinutes(30)->format('H:i:s'),
            'subtotal' => '500.00',
            'visit_charge' => '0.00',
            'discount' => '0.00',
            'total' => '500.00',
            'status' => 'pending',
            'customer_notes' => null,
            'address_line_1' => null,
            'created_by' => null,
        ];
    }
}
