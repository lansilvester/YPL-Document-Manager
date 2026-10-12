<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'name' => $this->faker->name(),
            'position' => $this->faker->jobTitle(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->numerify('08##########'),
            'sort_order' => 0,
        ];
    }
}
