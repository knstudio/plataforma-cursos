<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_can_download_attachment(): void
    {
        Storage::fake('local');
        [$course, $attachment] = $this->attachmentScenario();
        $student = User::factory()->create();
        Enrollment::factory()->for($student)->for($course)->create();

        $this->actingAs($student)
            ->get(route('attachments.show', $attachment))
            ->assertOk()
            ->assertDownload('apostila.pdf');
    }

    public function test_student_without_access_cannot_download_attachment(): void
    {
        Storage::fake('local');
        [, $attachment] = $this->attachmentScenario();

        $this->actingAs(User::factory()->create())
            ->get(route('attachments.show', $attachment))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_attachment(): void
    {
        Storage::fake('local');
        [, $attachment] = $this->attachmentScenario();

        $this->get(route('attachments.show', $attachment))->assertRedirect(route('login'));
    }

    /**
     * @return array{0: Course, 1: LessonAttachment}
     */
    private function attachmentScenario(): array
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for(Module::factory()->for($course))->create();

        Storage::disk('local')->put('attachments/apostila.pdf', '%PDF-1.4 fake');

        $attachment = LessonAttachment::factory()->for($lesson)->create([
            'path' => 'attachments/apostila.pdf',
            'original_name' => 'apostila.pdf',
        ]);

        return [$course, $attachment];
    }
}
