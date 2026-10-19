<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Only buyer data is accepted here: prices and products come from the database.
 */
class CheckoutRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'confirmed'],
            'phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            // IDs of the ticked order bumps; checked against the product's bumps in the controller.
            'order_bumps' => ['array'],
            'order_bumps.*' => ['integer'],
            // WhatsApp funnel enrollment that sent the buyer here (ignored when unknown).
            'fi' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Informe o WhatsApp com DDD (10 ou 11 números).',
            'email.confirmed' => 'Os e-mails não são iguais. Confira e digite novamente.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'WhatsApp',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'email_confirmation' => mb_strtolower(trim((string) $this->input('email_confirmation'))),
            'phone' => preg_replace('/\D/', '', (string) $this->input('phone')),
            'order_bumps' => array_values(array_filter((array) $this->input('order_bumps', []), 'is_numeric')),
            'fi' => ctype_digit((string) $this->input('fi')) ? (int) $this->input('fi') : null,
        ]);
    }
}
