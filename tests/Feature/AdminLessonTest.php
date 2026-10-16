<?php

namespace Tests\Feature;

use App\Enums\VideoProvider;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLessonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Course $course;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->course = Course::factory()->create();
        $this->module = Module::factory()->for($this->course)->create();
    }

    public function test_create_form_requires_a_module(): void
    {
        $emptyCourse = Course::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.lessons.create', $emptyCourse))
            ->assertRedirect(route('admin.courses.edit', $emptyCourse))
            ->assertSessionHas('error');
    }

    public function test_create_form_is_rendered(): void
    {
        $this->actingAs($this->admin)->get(route('admin.lessons.create', $this->course))
            ->assertOk()
            ->assertSee('Nova aula');
    }

    public function test_admin_can_create_lesson_from_vimeo_link(): void
    {
        Lesson::factory()->for($this->module)->create(['sort_order' => 1]);

        $this->actingAs($this->admin)->post(route('admin.lessons.store', $this->course), [
            'module_id' => $this->module->id,
            'title' => 'Guirlandas',
            'video_url' => 'https://vimeo.com/563692125',
            'duration' => '09:43',
            'content' => '<p>Aula sobre guirlandas</p>',
            'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $lesson = Lesson::query()->where('title', 'Guirlandas')->firstOrFail();
        $this->assertSame(VideoProvider::Vimeo, $lesson->video_provider);
        $this->assertSame('563692125', $lesson->video_id);
        $this->assertSame(583, $lesson->duration_seconds);
        $this->assertSame(2, $lesson->sort_order);
    }

    public function test_invalid_video_link_and_duration_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.lessons.store', $this->course), [
            'module_id' => $this->module->id,
            'title' => 'Aula',
            'video_url' => 'https://example.com/video',
            'duration' => 'dez minutos',
        ])->assertSessionHasErrors(['video_url', 'duration']);
    }

    public function test_module_must_belong_to_the_course(): void
    {
        $foreignModule = Module::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.lessons.store', $this->course), [
            'module_id' => $foreignModule->id,
            'title' => 'Aula',
        ])->assertSessionHasErrors('module_id');
    }

    public function test_admin_can_update_lesson_and_move_it_to_another_module(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $other = Module::factory()->for($this->course)->create();
        Lesson::factory()->for($other)->create(['sort_order' => 5]);

        $this->actingAs($this->admin)->put(route('admin.lessons.update', $lesson), [
            'module_id' => $other->id,
            'title' => 'Renomeada',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertRedirect(route('admin.lessons.edit', $lesson));

        $lesson->refresh();
        $this->assertSame('Renomeada', $lesson->title);
        $this->assertSame($other->id, $lesson->module_id);
        $this->assertSame(6, $lesson->sort_order);
        $this->assertSame(VideoProvider::Youtube, $lesson->video_provider);
        $this->assertFalse($lesson->is_published);
    }

    public function test_edit_page_prefills_video_link(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create(['video_id' => '563689407']);

        $this->actingAs($this->admin)->get(route('admin.lessons.edit', $lesson))
            ->assertOk()
            ->assertSee('https://vimeo.com/563689407');
    }

    public function test_admin_can_attach_and_remove_files(): void
    {
        Storage::fake('local');
        $lesson = Lesson::factory()->for($this->module)->create();

        $this->actingAs($this->admin)->post(route('admin.attachments.store', $lesson), [
            'file' => UploadedFile::fake()->create('apostila-essencial.pdf', 500, 'application/pdf'),
        ])->assertRedirect(route('admin.lessons.edit', $lesson));

        $attachment = $lesson->attachments()->firstOrFail();
        $this->assertSame('apostila-essencial', $attachment->title);
        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($this->admin)->delete(route('admin.attachments.destroy', $attachment));

        $this->assertModelMissing($attachment);
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_executable_files_are_rejected(): void
    {
        Storage::fake('local');
        $lesson = Lesson::factory()->for($this->module)->create();

        $this->actingAs($this->admin)->post(route('admin.attachments.store', $lesson), [
            'file' => UploadedFile::fake()->create('virus.php', 1),
        ])->assertSessionHasErrors('file', errorBag: 'attachment');
    }

    public function test_deleting_lesson_removes_attachment_files(): void
    {
        Storage::fake('local');
        $lesson = Lesson::factory()->for($this->module)->create();
        Storage::disk('local')->put('attachments/x.pdf', 'pdf');
        LessonAttachment::factory()->for($lesson)->create(['path' => 'attachments/x.pdf']);

        $this->actingAs($this->admin)->delete(route('admin.lessons.destroy', $lesson))
            ->assertRedirect(route('admin.courses.edit', $this->course));

        $this->assertModelMissing($lesson);
        Storage::disk('local')->assertMissing('attachments/x.pdf');
    }
}
