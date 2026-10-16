<?php

namespace Database\Factories;

use App\Enums\VideoProvider;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'title' => fake()->sentence(3),
            'video_provider' => VideoProvider::Vimeo,
            'video_id' => (string) fake()->numberBetween(100000000, 999999999),
            'duration_seconds' => fake()->numberBetween(60, 1200),
            'content' => fake()->paragraph(),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the lesson is hidden from students.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
