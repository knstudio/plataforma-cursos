<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * .env values still work as a fallback while nothing is saved in the admin.
     */
    private const ENV_FALLBACKS = [
        'mercadopago_access_token' => 'services.mercadopago.access_token',
        'mercadopago_webhook_secret' => 'services.mercadopago.webhook_secret',
        'hotmart_hottok' => 'services.hotmart.hottok',
    ];

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'pixelIds' => Setting::facebookPixelIds(),
            'supportEmail' => Setting::value('support_email', config('checkout.support_email')),
            'companyName' => Setting::value('company_name', config('checkout.company_name')),
            'companyDocument' => Setting::value('company_document', config('checkout.company_document')),
            'secrets' => collect(self::ENV_FALLBACKS)->map(fn (string $config, string $key) => $this->describeSecret($key, $config)),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $values = $request->safe()->except('clear_secrets');
        $clear = $request->validated('clear_secrets', []);

        foreach (Setting::SECRET_KEYS as $key) {
            if (in_array($key, $clear, true)) {
                $values[$key] = null;
            } elseif (blank($values[$key] ?? null)) {
                unset($values[$key]);
            }
        }

        Setting::store($values);

        return redirect()->route('admin.settings.edit')->with('status', 'Configurações salvas.');
    }

    /**
     * Only the last characters are shown, so the credential can be recognized without being exposed.
     *
     * @return array{hint: string|null, source: string|null}
     */
    private function describeSecret(string $key, string $config): array
    {
        $saved = Setting::value($key);
        $value = $saved ?? config($config);

        return [
            'hint' => filled($value) ? '••••••'.Str::substr($value, -6) : null,
            'source' => filled($saved) ? 'admin' : (filled($value) ? 'env' : null),
        ];
    }
}
