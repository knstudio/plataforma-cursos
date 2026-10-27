<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Key/value settings edited in Admin > Configurações (pixels, checkout footer, gateway credentials...).
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    /**
     * Credentials stored encrypted with APP_KEY and never shown back in the admin.
     */
    public const SECRET_KEYS = ['mercadopago_access_token', 'mercadopago_webhook_secret', 'hotmart_hottok'];

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function value(string $key, ?string $default = null): ?string
    {
        $settings = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        $value = $settings[$key] ?? null;

        if (blank($value)) {
            return $default;
        }

        if (in_array($key, self::SECRET_KEYS, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                // APP_KEY changed: the credential has to be typed again in the admin.
                return $default;
            }
        }

        return $value;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            if (filled($value) && in_array($key, self::SECRET_KEYS, true)) {
                $value = Crypt::encryptString($value);
            }

            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Bearer token of the purchase check API used by GoatFlow. Shown in the admin so it can be
     * copied into the flow; created on first use, replaced with $renew.
     */
    public static function integrationApiToken(bool $renew = false): string
    {
        $token = static::value('integration_api_token');

        if ($renew || blank($token)) {
            $token = Str::random(48);
            static::store(['integration_api_token' => $token]);
        }

        return $token;
    }

    /**
     * Facebook pixel IDs fired on every checkout.
     *
     * @return list<string>
     */
    public static function facebookPixelIds(): array
    {
        return self::parsePixelIds(static::value('facebook_pixel_ids'));
    }

    /**
     * Accepts IDs separated by commas, spaces or line breaks; keeps digits only.
     *
     * @return list<string>
     */
    public static function parsePixelIds(?string $value): array
    {
        $ids = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter($ids, fn (string $id) => ctype_digit($id))));
    }
}
