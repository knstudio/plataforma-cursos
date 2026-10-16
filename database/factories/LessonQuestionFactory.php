<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonQuestion>
 */
class LessonQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(12).'?',
            'is_hidden' => false,
        ];
    }

    /**
     * Indicate that the teacher already answered.
     */
    public function answered(): static
    {
        return $this->state(fn (array $attributes) => [
            'answered_at' => now(),
        ]);
    }

    /**
     * Indicate that the question was hidden by the admin.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
