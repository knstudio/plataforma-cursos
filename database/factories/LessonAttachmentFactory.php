<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonAttachment>
 */
class LessonAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->slug(2).'.pdf';

        return [
            'lesson_id' => Lesson::factory(),
            'title' => fake()->sentence(2),
            'path' => 'attachments/'.$fileName,
            'original_name' => $fileName,
            'size' => fake()->numberBetween(10_000, 5_000_000),
        ];
    }
}
