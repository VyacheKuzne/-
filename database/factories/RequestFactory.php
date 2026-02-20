<?php

namespace Database\Factories;

use App\Models\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        return [
            'clientName' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'phone' => substr($this->faker->phoneNumber(), 0, 12),
            'address' => substr($this->faker->streetAddress(), 0, 40),
            'problemText' => $this->faker->sentence(8),
            'status' => 'new',
            'assignedTo' => null,
        ];
    }

    public function assigned(int $masterId): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
            'assignedTo' => $masterId,
        ]);
    }
}