<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::factory();

        return [
            'tenant_id' => $tenant,
            'room_id' => Room::factory()->for($tenant),
            'child_id' => Child::factory()->for($tenant),
            'checked_in_at' => now(),
            'checked_out_at' => null,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_out_at' => now(),
        ]);
    }
}
