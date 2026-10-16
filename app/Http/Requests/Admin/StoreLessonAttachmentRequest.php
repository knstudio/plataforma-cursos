<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonAttachmentRequest extends FormRequest
{
    /**
     * Kept apart from the lesson form errors shown on the same page.
     *
     * @var string
     */
    protected $errorBag = 'attachment';

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
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:102400', 'extensions:pdf,zip,rar,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,mp3'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'file' => 'arquivo',
        ];
    }
}
