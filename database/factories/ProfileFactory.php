<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'account_number' => 'ACC' . $this->faker->unique()->numberBetween(100000, 999999),
            'tax_number' => 'TAX' . $this->faker->unique()->numberBetween(100000, 999999),
            'telephone_number' => $this->faker->phoneNumber,
            'street_address' => $this->faker->streetAddress,
            'city_town' => $this->faker->city,
            'parish' => $this->faker->randomElement([
                'St. Andrew', 'St. Catherine', 'Clarendon', 'Manchester', 
                'St. Elizabeth', 'Westmoreland', 'Hanover', 'St. James',
                'Trelawny', 'St. Ann', 'St. Mary', 'Portland', 'St. Thomas', 'Kingston'
            ]),
            'country' => 'Jamaica',
            'pickup_location' => $this->faker->numberBetween(1, 5), // Random pickup location ID
            'profile_photo_path' => null,
        ];
    }
}