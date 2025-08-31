<?php

namespace Database\Factories;

use App\Models\bill_series;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\bill_series>
 */
class BillSeriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = bill_series::class;
    public function definition(): array
    {
        $frequencies = ['monthly', 'annual', 'custom'];
        $selectedFrequency = $this->faker->randomElement($frequencies);

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence,
            'frequency' => $selectedFrequency,
            'custom_frequency_days' => $selectedFrequency === 'custom' ? $this->faker->numberBetween(1, 30) : null,
            'frequency_interval' => $this->faker->numberBetween(1, 3),
            'due_day' => $this->faker->numberBetween(1, 28),
            'start_date' => Carbon::now()->subMonths($this->faker->numberBetween(1, 12)),
            'is_active' => true,
        ];
    }
}
