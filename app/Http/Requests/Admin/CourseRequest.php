<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
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
        /** @var Course|null $course */
        $course = $this->route('course');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('courses', 'slug')->ignore($course?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'max:2048'],
            'remove_cover' => ['boolean'],
            'sales_url' => ['nullable', 'url:http,https', 'max:500'],
            'is_published' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'slug' => 'endereço (slug)',
            'short_description' => 'resumo',
            'cover' => 'capa',
            'sales_url' => 'link da página de vendas',
            'sort_order' => 'ordem',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('title', '')),
            'is_published' => $this->boolean('is_published'),
            'remove_cover' => $this->boolean('remove_cover'),
            'description' => $this->blankEditorToNull($this->input('description')),
        ]);
    }

    /**
     * Quill submits "<p><br></p>" for an empty editor.
     */
    private function blankEditorToNull(?string $html): ?string
    {
        return blank(trim(strip_tags((string) $html))) ? null : $html;
    }
}
