<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_only_sees_courses_with_active_enrollment(): void
    {
        $student = User::factory()->create();
        $enrolled = Course::factory()->create(['title' => 'Curso Liberado']);
        $expired = Course::factory()->create(['title' => 'Curso Expirado']);
        $revoked = Course::factory()->create(['title' => 'Curso Reembolsado']);
        Course::factory()->create(['title' => 'Curso Não Comprado']);

        Enrollment::factory()->for($student)->for($enrolled)->create();
        Enrollment::factory()->for($student)->for($expired)->expired()->create();
        Enrollment::factory()->for($student)->for($revoked)->revoked()->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Curso Liberado')
            ->assertDontSee('Curso Expirado')
            ->assertDontSee('Curso Reembolsado')
            ->assertDontSee('Curso Não Comprado');
    }

    public function test_courses_not_owned_are_offered_with_a_purchase_link(): void
    {
        $student = User::factory()->create();
        $owned = Course::factory()->create(['title' => 'Curso Comprado']);
        Enrollment::factory()->for($student)->for($owned)->create();

        Course::factory()->create(['title' => 'Curso Com Página', 'sales_url' => 'https://julianabaloes.com.br/curso-bubble/']);

        $sold = Course::factory()->create(['title' => 'Curso Com Checkout']);
        $bundle = Product::factory()->create(['slug' => 'combo', 'price' => 50]);
        $bundle->courses()->attach([$sold->id, Course::factory()->draft()->create()->id]);
        $single = Product::factory()->create(['slug' => 'so-o-curso', 'price' => 97]);
        $single->courses()->attach($sold);

        Course::factory()->create(['title' => 'Curso Sem Link']);
        Course::factory()->draft()->create(['title' => 'Curso Rascunho', 'sales_url' => 'https://example.com']);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Curso Comprado', 'Outros cursos da Juliana Balões', 'Curso Com Checkout', 'Curso Com Página'])
            ->assertSee('https://julianabaloes.com.br/curso-bubble/')
            ->assertSee(route('checkout.show', $single))
            ->assertDontSee(route('checkout.show', $bundle))
            ->assertDontSee('Curso Sem Link')
            ->assertDontSee('Curso Rascunho');
    }

    public function test_offer_section_is_hidden_when_student_owns_everything(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['sales_url' => 'https://example.com']);
        Enrollment::factory()->for($student)->for($course)->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Outros cursos da Juliana Balões');
    }

    public function test_unpublished_courses_are_hidden_from_students(): void
    {
        $student = User::factory()->create();
        $draft = Course::factory()->draft()->create(['title' => 'Curso Rascunho']);
        Enrollment::factory()->for($student)->for($draft)->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Curso Rascunho');
    }

    public function test_dashboard_shows_lesson_progress(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        $lessons = Lesson::factory()->for($module)->count(4)->create();
        Enrollment::factory()->for($student)->for($course)->create();

        $student->completedLessons()->attach($lessons->first()->id, ['completed_at' => now()]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aulas: 1 de 4')
            ->assertSee('Continuar');
    }

    public function test_admin_sees_every_published_course(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->create(['title' => 'Curso Qualquer']);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Curso Qualquer');
    }
}
