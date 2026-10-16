<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_lesson_records_progress_and_moves_to_the_next(): void
    {
        [$student, $course, $first, $second] = $this->scenario();

        $this->actingAs($student)
            ->post(route('courses.lessons.complete', [$course, $first]))
            ->assertRedirect(route('courses.lessons.show', [$course, $second]));

        $this->assertDatabaseHas('lesson_completions', ['user_id' => $student->id, 'lesson_id' => $first->id]);
    }

    public function test_completing_the_last_lesson_stays_on_it(): void
    {
        [$student, $course, , $second] = $this->scenario();

        $this->actingAs($student)
            ->post(route('courses.lessons.complete', [$course, $second]))
            ->assertRedirect(route('courses.lessons.show', [$course, $second]));
    }

    public function test_completing_twice_does_not_duplicate(): void
    {
        [$student, $course, $first] = $this->scenario();

        $this->actingAs($student)->post(route('courses.lessons.complete', [$course, $first]));
        $this->actingAs($student)->post(route('courses.lessons.complete', [$course, $first]));

        $this->assertDatabaseCount('lesson_completions', 1);
    }

    public function test_lesson_can_be_unmarked(): void
    {
        [$student, $course, $first] = $this->scenario();
        $student->completedLessons()->attach($first->id, ['completed_at' => now()]);

        $this->actingAs($student)
            ->delete(route('courses.lessons.uncomplete', [$course, $first]))
            ->assertRedirect(route('courses.lessons.show', [$course, $first]));

        $this->assertDatabaseCount('lesson_completions', 0);
    }

    public function test_student_without_access_cannot_complete_lessons(): void
    {
        [, $course, $first] = $this->scenario();

        $this->actingAs(User::factory()->create())
            ->post(route('courses.lessons.complete', [$course, $first]))
            ->assertForbidden();

        $this->assertDatabaseCount('lesson_completions', 0);
    }

    /**
     * @return array{0: User, 1: Course, 2: Lesson, 3: Lesson}
     */
    private function scenario(): array
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        $first = Lesson::factory()->for($module)->create(['sort_order' => 1]);
        $second = Lesson::factory()->for($module)->create(['sort_order' => 2]);
        Enrollment::factory()->for($student)->for($course)->create();

        return [$student, $course, $first, $second];
    }
}
