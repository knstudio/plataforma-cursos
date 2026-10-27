<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesPixelIds;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    use NormalizesPixelIds;

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
            'facebook_pixel_ids' => ['nullable', 'string', 'max:1000', 'regex:/^\d+(,\d+)*$/'],
            'support_email' => ['required', 'email', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_document' => ['nullable', 'string', 'max:30'],
            // Credentials: left blank keeps the saved value.
            'mercadopago_access_token' => ['nullable', 'string', 'max:255', 'regex:/^\S+$/'],
            'mercadopago_webhook_secret' => ['nullable', 'string', 'max:255', 'regex:/^\S+$/'],
            'hotmart_hottok' => ['nullable', 'string', 'max:255', 'regex:/^\S+$/'],
            'renew_integration_token' => ['boolean'],
            'clear_secrets' => ['array'],
            'clear_secrets.*' => [Rule::in(Setting::SECRET_KEYS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'facebook_pixel_ids.regex' => 'Os IDs de pixel devem conter só números (separe vários por vírgula ou um por linha).',
            'mercadopago_access_token.regex' => 'O Access Token não pode conter espaços.',
            'mercadopago_webhook_secret.regex' => 'A assinatura secreta não pode conter espaços.',
            'hotmart_hottok.regex' => 'O hottok não pode conter espaços.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'facebook_pixel_ids' => 'pixels do Facebook',
            'support_email' => 'e-mail de suporte',
            'company_name' => 'nome da empresa',
            'company_document' => 'CNPJ',
            'mercadopago_access_token' => 'Access Token',
            'mercadopago_webhook_secret' => 'assinatura secreta',
            'hotmart_hottok' => 'hottok',
        ];
    }

    protected function prepareForValidation(): void
    {
        $secrets = collect(Setting::SECRET_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => trim((string) $this->input($key))])
            ->all();

        $this->merge([
            'facebook_pixel_ids' => $this->normalizePixelIds($this->input('facebook_pixel_ids')),
            'renew_integration_token' => $this->boolean('renew_integration_token'),
            ...$secrets,
        ]);
    }
}
