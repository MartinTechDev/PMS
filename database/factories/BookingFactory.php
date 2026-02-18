<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $checkIn = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $checkOut = $this->faker->dateTimeBetween($checkIn, '+2 months');

        return [
            'external_id' => $this->faker->unique()->randomNumber(5),
            'room_id' => Room::factory(),
            'guest_id' => Guest::factory(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'status' => $this->faker->randomElement(['confirmed', 'pending', 'cancelled']),
            'total_price' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
