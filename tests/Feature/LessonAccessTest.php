<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonAccessTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Lesson $firstLesson;

    private Lesson $secondLesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        $module = Module::factory()->for($this->course)->create();
        $this->firstLesson = Lesson::factory()->for($module)->create(['title' => 'Primeira aula', 'sort_order' => 1, 'video_id' => '536759157']);
        $this->secondLesson = Lesson::factory()->for($module)->create(['title' => 'Segunda aula', 'sort_order' => 2]);
    }

    public function test_enrolled_student_can_watch_a_lesson(): void
    {
        $student = $this->enrolledStudent();

        $this->actingAs($student)
            ->get(route('courses.lessons.show', [$this->course, $this->firstLesson]))
            ->assertOk()
            ->assertSee('Primeira aula')
            ->assertSee('https://player.vimeo.com/video/536759157', false)
            ->assertSee(route('courses.lessons.show', [$this->course, $this->secondLesson]), false);
    }

    public function test_student_without_enrollment_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('courses.lessons.show', [$this->course, $this->firstLesson]))
            ->assertForbidden();
    }

    public function test_expired_enrollment_is_forbidden(): void
    {
        $student = User::factory()->create();
        Enrollment::factory()->for($student)->for($this->course)->expired()->create();

        $this->actingAs($student)
            ->get(route('courses.lessons.show', [$this->course, $this->firstLesson]))
            ->assertForbidden();
    }

    public function test_lesson_from_another_course_returns_not_found(): void
    {
        $student = $this->enrolledStudent();
        $foreignLesson = Lesson::factory()->create();

        $this->actingAs($student)
            ->get(route('courses.lessons.show', [$this->course, $foreignLesson]))
            ->assertNotFound();
    }

    public function test_unpublished_lesson_is_hidden_from_students(): void
    {
        $student = $this->enrolledStudent();
        $this->secondLesson->update(['is_published' => false]);

        $this->actingAs($student)
            ->get(route('courses.lessons.show', [$this->course, $this->secondLesson]))
            ->assertNotFound();
    }

    public function test_course_page_redirects_to_first_unfinished_lesson(): void
    {
        $student = $this->enrolledStudent();

        $this->actingAs($student)
            ->get(route('courses.show', $this->course))
            ->assertRedirect(route('courses.lessons.show', [$this->course, $this->firstLesson]));

        $student->completedLessons()->attach($this->firstLesson->id, ['completed_at' => now()]);

        $this->actingAs($student)
            ->get(route('courses.show', $this->course))
            ->assertRedirect(route('courses.lessons.show', [$this->course, $this->secondLesson]));
    }

    public function test_course_without_lessons_sends_student_back_to_dashboard(): void
    {
        $student = User::factory()->create();
        $emptyCourse = Course::factory()->create();
        Enrollment::factory()->for($student)->for($emptyCourse)->create();

        $this->actingAs($student)
            ->get(route('courses.show', $emptyCourse))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    private function enrolledStudent(): User
    {
        $student = User::factory()->create();
        Enrollment::factory()->for($student)->for($this->course)->create();

        return $student;
    }
}
