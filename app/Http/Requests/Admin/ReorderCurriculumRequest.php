<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Payload sent by the drag-and-drop curriculum editor:
 * modules in display order, each with its lessons in display order.
 */
class ReorderCurriculumRequest extends FormRequest
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
            'modules' => ['required', 'array'],
            'modules.*.id' => ['required', 'integer', 'distinct'],
            'modules.*.lessons' => ['present', 'array'],
            'modules.*.lessons.*' => ['integer', 'distinct'],
        ];
    }
}
