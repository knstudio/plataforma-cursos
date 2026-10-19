<?php

namespace App\Http\Requests\Admin;

use App\Models\Funil;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FunilRequest extends FormRequest
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
        /** @var Funil|null $funil */
        $funil = $this->route('funil');

        return [
            'nome' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('funil_funis', 'slug')->ignore($funil?->id)],
            'instancia' => ['required', 'string', 'max:60', 'regex:/^\S+$/'],
            'prioridade' => ['required', 'integer', 'min:-100', 'max:100'],
            'hora_inicio_envio' => ['required', 'integer', 'min:0', 'max:23'],
            'hora_fim_envio' => ['required', 'integer', 'min:1', 'max:24', 'gt:hora_inicio_envio'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'instancia.regex' => 'O nome da instância não pode ter espaços.',
            'hora_fim_envio.gt' => 'O fim do horário de envio precisa ser depois do início.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'slug' => 'identificador',
            'instancia' => 'instância do WhatsApp',
            'hora_inicio_envio' => 'início do horário de envio',
            'hora_fim_envio' => 'fim do horário de envio',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('nome', '')),
            'instancia' => trim((string) $this->input('instancia')),
            'prioridade' => filled($this->input('prioridade')) ? $this->input('prioridade') : 0,
            'ativo' => $this->boolean('ativo'),
        ]);
    }
}
