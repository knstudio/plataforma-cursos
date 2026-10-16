<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Support\Duration;
use App\Support\VideoUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('course_id', $this->course()->id)],
            'title' => ['required', 'string', 'max:255'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'string', 'max:10'],
            'content' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (filled($this->input('video_url')) && VideoUrl::parse($this->input('video_url')) === null) {
                    $validator->errors()->add('video_url', 'Não reconheci este link. Use um link do Vimeo, YouTube ou Panda Video.');
                }

                if (Duration::toSeconds($this->input('duration')) === false) {
                    $validator->errors()->add('duration', 'Use o formato mm:ss (ex.: 05:46) ou h:mm:ss.');
                }
            },
        ];
    }

    /**
     * Attributes ready to be saved on the lesson.
     *
     * @return array<string, mixed>
     */
    public function lessonAttributes(): array
    {
        $video = VideoUrl::parse($this->validated('video_url'));

        return [
            'module_id' => (int) $this->validated('module_id'),
            'title' => $this->validated('title'),
            'video_provider' => $video['provider'] ?? null,
            'video_id' => $video['id'] ?? null,
            'duration_seconds' => Duration::toSeconds($this->validated('duration')) ?: null,
            'content' => $this->validated('content'),
            'is_published' => $this->validated('is_published'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'module_id' => 'módulo',
            'title' => 'título',
            'video_url' => 'link do vídeo',
            'duration' => 'duração',
            'content' => 'descrição',
        ];
    }

    protected function prepareForValidation(): void
    {
        $content = $this->input('content');

        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'content' => blank(trim(strip_tags((string) $content, '<img><iframe>'))) ? null : $content,
        ]);
    }

    /**
     * The course the lesson belongs to: from the route on create, from the lesson on update.
     */
    private function course(): Course
    {
        $course = $this->route('course');

        if ($course instanceof Course) {
            return $course;
        }

        /** @var Lesson $lesson */
        $lesson = $this->route('lesson');

        return $lesson->module->course;
    }
}
