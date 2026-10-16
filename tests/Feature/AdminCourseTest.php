<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCourseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_sees_course_list(): void
    {
        Course::factory()->create(['title' => 'Escultura Avançada']);

        $this->actingAs($this->admin)->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSee('Escultura Avançada');
    }

    public function test_students_cannot_manage_courses(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.courses.store'), ['title' => 'Hack'])
            ->assertForbidden();

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_admin_can_create_course_with_cover(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), [
            'title' => 'Arcos Desconstruídos',
            'short_description' => 'Do zero ao arco orgânico',
            'description' => '<p>Descrição</p>',
            'is_published' => '1',
            'cover' => UploadedFile::fake()->image('capa.jpg', 800, 600),
        ]);

        $course = Course::query()->firstOrFail();

        $response->assertRedirect(route('admin.courses.edit', $course));
        $this->assertSame('arcos-desconstruidos', $course->slug);
        $this->assertTrue($course->is_published);
        Storage::disk('public')->assertExists($course->cover_image);
    }

    public function test_empty_editor_is_saved_as_null(): void
    {
        $this->actingAs($this->admin)->post(route('admin.courses.store'), [
            'title' => 'Curso Sem Descrição',
            'description' => '<p><br></p>',
        ]);

        $this->assertNull(Course::query()->firstOrFail()->description);
    }

    public function test_slug_must_be_unique(): void
    {
        Course::factory()->create(['slug' => 'baloes']);

        $this->actingAs($this->admin)->post(route('admin.courses.store'), ['title' => 'Balões'])
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_course_and_remove_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('courses/old.jpg', 'x');
        $course = Course::factory()->create(['cover_image' => 'courses/old.jpg']);

        $this->actingAs($this->admin)->put(route('admin.courses.update', $course), [
            'title' => 'Novo Título',
            'slug' => $course->slug,
            'remove_cover' => '1',
        ])->assertRedirect(route('admin.courses.edit', $course));

        $course->refresh();
        $this->assertSame('Novo Título', $course->title);
        $this->assertFalse($course->is_published);
        $this->assertNull($course->cover_image);
        Storage::disk('public')->assertMissing('courses/old.jpg');
    }

    public function test_course_edit_page_lists_modules_and_lessons(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create(['title' => 'Módulo Um']);
        Lesson::factory()->for($module)->create(['title' => 'Aula Inicial']);

        $this->actingAs($this->admin)->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('Módulo Um')
            ->assertSee('Aula Inicial');
    }

    public function test_course_with_students_cannot_be_deleted(): void
    {
        $course = Course::factory()->create();
        Enrollment::factory()->for($course)->create();

        $this->actingAs($this->admin)->delete(route('admin.courses.destroy', $course))
            ->assertSessionHas('error');

        $this->assertModelExists($course);
    }

    public function test_deleting_course_removes_its_files(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for(Module::factory()->for($course))->create();
        Storage::disk('local')->put('attachments/1/apostila.pdf', 'pdf');
        LessonAttachment::factory()->for($lesson)->create(['path' => 'attachments/1/apostila.pdf']);

        $this->actingAs($this->admin)->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertModelMissing($course);
        $this->assertDatabaseCount('lessons', 0);
        Storage::disk('local')->assertMissing('attachments/1/apostila.pdf');
    }

    public function test_admin_can_manage_modules(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.modules.store', $course), ['title' => 'Bônus'])
            ->assertRedirect(route('admin.courses.edit', $course));

        $module = $course->modules()->firstOrFail();
        $this->assertSame('Bônus', $module->title);

        $this->actingAs($this->admin)->put(route('admin.modules.update', $module), ['title' => 'Bônus Exclusivos']);
        $this->assertSame('Bônus Exclusivos', $module->fresh()->title);

        $this->actingAs($this->admin)->delete(route('admin.modules.destroy', $module));
        $this->assertModelMissing($module);
    }

    public function test_module_with_lessons_cannot_be_deleted(): void
    {
        $module = Module::factory()->create();
        Lesson::factory()->for($module)->create();

        $this->actingAs($this->admin)->delete(route('admin.modules.destroy', $module))
            ->assertSessionHas('error');

        $this->assertModelExists($module);
    }
}
