<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\Module;
use App\Models\User;
use App\Notifications\LessonQuestionAnswered;
use App\Notifications\NewLessonQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LessonQuestionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $admin;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        $this->lesson = Lesson::factory()->for(Module::factory()->for($this->course))->create(['title' => 'Flor de balão']);
        $this->student = User::factory()->create(['name' => 'Maria Souza']);
        Enrollment::factory()->for($this->student)->for($this->course)->create();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_student_asks_a_question_and_admins_are_notified(): void
    {
        Notification::fake();

        $this->actingAs($this->student)
            ->post(route('courses.lessons.questions.store', [$this->course, $this->lesson]), ['body' => '  Qual o tamanho do balão?  '])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $question = LessonQuestion::query()->sole();
        $this->assertSame('Qual o tamanho do balão?', $question->body);
        $this->assertSame($this->student->id, $question->user_id);
        Notification::assertSentTo($this->admin, NewLessonQuestion::class);
        Notification::assertNotSentTo($this->student, NewLessonQuestion::class);
    }

    public function test_questions_are_public_to_students_of_the_course(): void
    {
        LessonQuestion::factory()->for($this->lesson)->for($this->student)->create(['body' => 'Pergunta visível']);
        LessonQuestion::factory()->for($this->lesson)->hidden()->create(['body' => 'Pergunta ofensiva']);

        $classmate = User::factory()->create();
        Enrollment::factory()->for($classmate)->for($this->course)->create();

        $this->actingAs($classmate)->get(route('courses.lessons.show', [$this->course, $this->lesson]))
            ->assertOk()
            ->assertSee('Dúvidas sobre esta aula')
            ->assertSee('Pergunta visível')
            ->assertSee('Maria Souza')
            ->assertDontSee('Pergunta ofensiva');

        $this->actingAs($this->admin)->get(route('courses.lessons.show', [$this->course, $this->lesson]))
            ->assertSee('Pergunta ofensiva')
            ->assertSee('Oculta para os alunos');
    }

    public function test_students_without_access_cannot_ask(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->post(route('courses.lessons.questions.store', [$this->course, $this->lesson]), ['body' => 'Posso perguntar?'])
            ->assertForbidden();

        $this->assertDatabaseCount('lesson_questions', 0);
    }

    public function test_question_must_have_content(): void
    {
        $this->actingAs($this->student)
            ->post(route('courses.lessons.questions.store', [$this->course, $this->lesson]), ['body' => ' oi '])
            ->assertSessionHasErrorsIn('question', 'body');
    }

    public function test_admin_answers_and_student_is_notified(): void
    {
        Notification::fake();
        $question = LessonQuestion::factory()->for($this->lesson)->for($this->student)->create();

        $this->actingAs($this->admin)->get(route('admin.questions.index'))
            ->assertOk()
            ->assertSee($question->body);

        $this->actingAs($this->admin)
            ->from(route('admin.questions.show', $question))
            ->post(route('admin.answers.store', $question), ['body' => 'Use o balão 260.'])
            ->assertRedirect(route('admin.questions.show', $question).'#pergunta-'.$question->id);

        $this->assertTrue($question->fresh()->isAnswered());
        Notification::assertSentTo($this->student, LessonQuestionAnswered::class);

        $this->actingAs($this->student)->get(route('courses.lessons.show', [$this->course, $this->lesson]))
            ->assertSee('Use o balão 260.')
            ->assertSee('Professora');

        $this->actingAs($this->admin)->get(route('admin.questions.index'))
            ->assertDontSee($question->body);
    }

    public function test_deleting_the_only_answer_reopens_the_question(): void
    {
        $question = LessonQuestion::factory()->for($this->lesson)->answered()->create();
        $answer = $question->answers()->create(['user_id' => $this->admin->id, 'body' => 'Resposta']);

        $this->actingAs($this->admin)->delete(route('admin.answers.destroy', $answer))->assertRedirect();

        $this->assertFalse($question->fresh()->isAnswered());
    }

    public function test_admin_can_hide_and_delete_questions(): void
    {
        $question = LessonQuestion::factory()->for($this->lesson)->create();

        $this->actingAs($this->admin)->patch(route('admin.questions.visibility', $question))->assertRedirect();
        $this->assertTrue($question->fresh()->is_hidden);

        $this->actingAs($this->admin)->delete(route('admin.questions.destroy', $question))
            ->assertRedirect(route('admin.questions.index'));
        $this->assertModelMissing($question);
    }

    public function test_student_can_delete_own_unanswered_question_only(): void
    {
        $own = LessonQuestion::factory()->for($this->lesson)->for($this->student)->create();
        $answered = LessonQuestion::factory()->for($this->lesson)->for($this->student)->answered()->create();
        $other = LessonQuestion::factory()->for($this->lesson)->create();

        $this->actingAs($this->student)->delete(route('questions.destroy', $own))->assertRedirect();
        $this->actingAs($this->student)->delete(route('questions.destroy', $answered))->assertForbidden();
        $this->actingAs($this->student)->delete(route('questions.destroy', $other))->assertForbidden();

        $this->assertModelMissing($own);
        $this->assertModelExists($answered);
    }

    public function test_students_cannot_answer(): void
    {
        $question = LessonQuestion::factory()->for($this->lesson)->create();

        $this->actingAs($this->student)
            ->post(route('admin.answers.store', $question), ['body' => 'Eu sei!'])
            ->assertForbidden();
    }
}
