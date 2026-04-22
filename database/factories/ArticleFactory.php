<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
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
            'title' => $this->faker->sentence(),
            'authors' => $this->faker->name() . ', ' . $this->faker->name(),
            'abstract' => $this->faker->paragraph(),
            'url' => $this->faker->url(),
            'file_path' => null,
        ];
    }
}
