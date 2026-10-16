<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_pixels_and_checkout_footer(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'facebook_pixel_ids' => "2395963517103497\n451180756322749, 2395963517103497",
            'support_email' => 'suporte@julianabaloes.com.br',
            'company_name' => 'Juliana Balões',
            'company_document' => '26.084.018/0001-02',
        ])->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();

        $this->assertSame(['2395963517103497', '451180756322749'], Setting::facebookPixelIds());
        $this->assertSame('suporte@julianabaloes.com.br', Setting::value('support_email'));

        $product = Product::factory()->create();
        $this->get(route('checkout.show', $product))
            ->assertSee('suporte@julianabaloes.com.br')
            ->assertSee("fbq('init', '451180756322749')", false);
    }

    public function test_pixels_can_be_removed(): void
    {
        Setting::store(['facebook_pixel_ids' => '123']);

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), [
            'facebook_pixel_ids' => '',
            'support_email' => 'a@example.com',
            'company_name' => 'Empresa',
        ])->assertSessionHasNoErrors();

        $this->assertSame([], Setting::facebookPixelIds());
    }

    public function test_invalid_pixel_id_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), [
            'facebook_pixel_ids' => 'meu-pixel',
            'support_email' => 'a@example.com',
            'company_name' => 'Empresa',
        ])->assertSessionHasErrors('facebook_pixel_ids');
    }

    public function test_credentials_are_saved_encrypted_and_used_by_the_gateways(): void
    {
        config(['services.mercadopago.access_token' => 'ENV-token']);

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), [
            'support_email' => 'a@example.com',
            'company_name' => 'Empresa',
            'mercadopago_access_token' => ' APP_USR-123-abcdef ',
            'mercadopago_webhook_secret' => 'segredo-webhook',
            'hotmart_hottok' => 'hottok-123',
        ])->assertSessionHasNoErrors();

        $stored = Setting::query()->find('mercadopago_access_token')->value;
        $this->assertStringNotContainsString('APP_USR', $stored);
        $this->assertSame('APP_USR-123-abcdef', Setting::value('mercadopago_access_token'));

        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 1])]);
        app(MercadoPagoClient::class)->payment('1');
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer APP_USR-123-abcdef'));

        $this->postJson(route('webhooks.hotmart'), ['event' => 'PURCHASE_APPROVED'], ['X-HOTMART-HOTTOK' => 'errado'])
            ->assertUnauthorized();
    }

    public function test_blank_credential_keeps_the_saved_one_and_page_hides_it(): void
    {
        Setting::store(['mercadopago_access_token' => 'APP_USR-secreto-987654']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'support_email' => 'a@example.com',
            'company_name' => 'Empresa',
            'mercadopago_access_token' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('APP_USR-secreto-987654', Setting::value('mercadopago_access_token'));

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('987654')
            ->assertDontSee('APP_USR-secreto');
    }

    public function test_saved_credential_can_be_cleared(): void
    {
        Setting::store(['hotmart_hottok' => 'hottok-antigo']);

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), [
            'support_email' => 'a@example.com',
            'company_name' => 'Empresa',
            'clear_secrets' => ['hotmart_hottok'],
        ])->assertSessionHasNoErrors();

        $this->assertNull(Setting::value('hotmart_hottok'));
    }

    public function test_students_cannot_change_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }
}
