<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => 'CUS-'.now('Asia/Kolkata')->format('Y').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'name' => $this->faker->name(),
            'mobile' => $this->faker->unique()->numerify('9#########'),
            'email' => $this->faker->safeEmail(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'date_of_birth' => $this->faker->optional()->date(),
            'anniversary_date' => $this->faker->optional()->date(),
            'address_line_1' => $this->faker->optional()->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'pincode' => $this->faker->optional()->postcode(),
            'notes' => $this->faker->optional()->sentence(),
            'total_visits' => 0,
            'total_spent' => '0.00',
            'last_visit_at' => null,
            'status' => 'active',
        ];
    }
}
