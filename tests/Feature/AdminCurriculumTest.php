<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCurriculumTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reorder_modules_and_move_lessons(): void
    {
        $course = Course::factory()->create();
        $first = Module::factory()->for($course)->create(['sort_order' => 1]);
        $second = Module::factory()->for($course)->create(['sort_order' => 2]);
        $a = Lesson::factory()->for($first)->create(['sort_order' => 1]);
        $b = Lesson::factory()->for($first)->create(['sort_order' => 2]);
        $c = Lesson::factory()->for($second)->create(['sort_order' => 1]);

        $this->actingAs(User::factory()->admin()->create())
            ->putJson(route('admin.courses.curriculum.update', $course), [
                'modules' => [
                    ['id' => $second->id, 'lessons' => [$c->id, $a->id]],
                    ['id' => $first->id, 'lessons' => [$b->id]],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame($second->id, $a->fresh()->module_id);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(1, $b->fresh()->sort_order);
        $this->assertSame(
            [$c->id, $a->id, $b->id],
            $course->orderedLessons()->pluck('id')->all(),
        );
    }

    public function test_lessons_from_another_course_are_rejected(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        $foreignLesson = Lesson::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->putJson(route('admin.courses.curriculum.update', $course), [
                'modules' => [['id' => $module->id, 'lessons' => [$foreignLesson->id]]],
            ])
            ->assertUnprocessable();

        $this->assertNotSame($module->id, $foreignLesson->fresh()->module_id);
    }

    public function test_incomplete_structure_is_rejected(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        Lesson::factory()->for($module)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->putJson(route('admin.courses.curriculum.update', $course), [
                'modules' => [['id' => $module->id, 'lessons' => []]],
            ])
            ->assertUnprocessable();
    }
}
