<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesPixelIds;
use App\Http\Requests\Concerns\ParsesBrazilianDecimals;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    use NormalizesPixelIds, ParsesBrazilianDecimals;

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
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'access_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'hotmart_product_id' => ['nullable', 'string', 'max:50', Rule::unique('products', 'hotmart_product_id')->ignore($product?->id)],
            'is_active' => ['boolean'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'order_bump_product_id' => ['nullable', 'integer', Rule::exists('products', 'id'), Rule::notIn(array_filter([$product?->id]))],
            'order_bump_price' => ['nullable', 'required_with:order_bump_product_id', 'numeric', 'min:0', 'max:99999999'],
            'order_bump_headline' => ['nullable', 'string', 'max:500'],
            'checkout_banner_upload' => ['nullable', 'image', 'max:2048'],
            'remove_checkout_banner' => ['boolean'],
            'checkout_timer_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'facebook_pixel_ids' => ['nullable', 'string', 'max:500', 'regex:/^\d+(,\d+)*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'slug' => 'endereço (slug)',
            'price' => 'preço',
            'access_days' => 'dias de acesso',
            'hotmart_product_id' => 'ID do produto na Hotmart',
            'course_ids' => 'cursos',
            'order_bump_product_id' => 'produto do order bump',
            'order_bump_price' => 'preço do order bump',
            'order_bump_headline' => 'chamada do order bump',
            'checkout_banner_upload' => 'banner do checkout',
            'checkout_timer_minutes' => 'minutos do contador',
            'facebook_pixel_ids' => 'pixels do Facebook',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'price' => $this->decimal($this->input('price')),
            'order_bump_product_id' => filled($this->input('order_bump_product_id')) ? $this->input('order_bump_product_id') : null,
            'order_bump_price' => $this->decimal($this->input('order_bump_price')),
            'is_active' => $this->boolean('is_active'),
            'remove_checkout_banner' => $this->boolean('remove_checkout_banner'),
            'facebook_pixel_ids' => $this->normalizePixelIds($this->input('facebook_pixel_ids')),
            'checkout_timer_minutes' => filled($this->input('checkout_timer_minutes')) ? $this->input('checkout_timer_minutes') : 0,
            'hotmart_product_id' => filled($this->input('hotmart_product_id')) ? trim($this->input('hotmart_product_id')) : null,
            'access_days' => filled($this->input('access_days')) ? $this->input('access_days') : null,
        ]);
    }
}
