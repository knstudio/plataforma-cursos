<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ParsesWaitTimes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FunilBlocoRequest extends FormRequest
{
    use ParsesWaitTimes;

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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            ...$this->waitRules('espera_inicio'),
            'usa_espera_compra' => ['boolean'],
            ...$this->waitRules('espera_compra', required: false),
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForSave(): array
    {
        return [
            'product_id' => $this->validated('product_id'),
            'espera_inicio_seg' => $this->waitSeconds('espera_inicio'),
            'espera_se_comprou_seg' => $this->validated('usa_espera_compra') ? $this->waitSeconds('espera_compra') : null,
            'ativo' => $this->validated('ativo'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'produto',
            'espera_inicio_valor' => 'espera do início',
            'espera_compra_valor' => 'espera se comprou',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'usa_espera_compra' => $this->boolean('usa_espera_compra'),
            'ativo' => $this->boolean('ativo'),
        ]);
    }
}
