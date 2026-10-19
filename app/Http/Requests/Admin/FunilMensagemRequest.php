<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ParsesWaitTimes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FunilMensagemRequest extends FormRequest
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
            'texto' => ['required', 'string', 'max:4000'],
            ...$this->waitRules('espera'),
            'product_offer_id' => ['nullable', 'integer', 'exists:product_offers,id'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForSave(): array
    {
        return [
            'texto' => $this->validated('texto'),
            'espera_seg' => $this->waitSeconds('espera'),
            'product_offer_id' => $this->validated('product_offer_id'),
            'ativo' => $this->validated('ativo'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'espera_valor' => 'espera',
            'product_offer_id' => 'oferta',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_offer_id' => $this->input('product_offer_id') ?: null,
            'ativo' => $this->boolean('ativo'),
        ]);
    }
}
