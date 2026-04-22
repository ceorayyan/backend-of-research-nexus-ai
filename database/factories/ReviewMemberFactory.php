<?php

namespace Database\Factories;

use App\Models\ReviewMember;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewMember>
 */
class ReviewMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement(['reviewer', 'coordinator', 'observer']),
            'invited_at' => $this->faker->dateTime(),
            'accepted_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
