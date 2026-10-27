<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Contract C:\laragon\www\integracao-juliana\CONTRATO.md — 2.1 (purchase check) and 2.2 (purchase notice).
 */
class GoatflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const FLOW_URL = 'https://knflow.test/api/flows/hook/abc';

    private Product $essencial;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->essencial = Product::factory()->create(['name' => 'Treinamento Essencial', 'slug' => 'treinamento-essencial', 'price' => 17]);
        $this->token = Setting::integrationApiToken();
    }

    // ---- 2.1 Consulta "comprou?" ----

    public function test_check_finds_purchase_by_phone_in_any_format(): void
    {
        $user = User::factory()->create(['phone' => '82999991234']);
        Order::factory()->for($user)->create(['product_id' => $this->essencial->id]);

        foreach (['5582999991234', '(82) 99999-1234', '558299991234', '+55 82 99999-1234'] as $telefone) {
            $this->consultar(['telefone' => $telefone])->assertOk()->assertExactJson(['comprou' => true]);
        }

        $this->consultar(['telefone' => '5582988887777'])->assertOk()->assertExactJson(['comprou' => false]);
    }

    public function test_check_finds_purchase_by_email_and_as_order_bump(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.com', 'phone' => null]);
        $order = Order::factory()->for($user)->create(['product_id' => Product::factory()->create()->id]);
        $order->items()->create(['product_id' => $this->essencial->id, 'description' => 'Bump', 'unit_price' => 12, 'is_order_bump' => true]);

        $this->consultar(['email' => ' MARIA@example.com '])->assertOk()->assertExactJson(['comprou' => true]);
    }

    public function test_check_ignores_orders_that_are_not_approved(): void
    {
        $user = User::factory()->create(['phone' => '82999991234']);
        Order::factory()->for($user)->pending()->create(['product_id' => $this->essencial->id]);
        Order::factory()->for($user)->create(['product_id' => $this->essencial->id, 'status' => 'refunded']);

        $this->consultar(['telefone' => '5582999991234'])->assertOk()->assertExactJson(['comprou' => false]);
    }

    public function test_check_errors(): void
    {
        $this->getJson(route('api.funil.comprou', ['produto' => 'treinamento-essencial', 'telefone' => '5582999991234']))->assertUnauthorized();
        $this->getJson(route('api.funil.comprou', ['produto' => 'treinamento-essencial', 'telefone' => '5582999991234']), ['Authorization' => 'Bearer errado'])->assertUnauthorized();
        $this->consultar(['telefone' => '5582999991234'], 'nao-existe')->assertNotFound();
        $this->consultar([])->assertUnprocessable();
        $this->consultar(['telefone' => '123'])->assertUnprocessable();
    }

    public function test_renewed_token_replaces_the_old_one(): void
    {
        $antigo = $this->token;
        $this->token = Setting::integrationApiToken(renew: true);

        $this->assertNotSame($antigo, $this->token);
        $this->consultar(['telefone' => '5582999991234'])->assertOk();
        $this->getJson(route('api.funil.comprou', ['produto' => 'treinamento-essencial', 'telefone' => '5582999991234']), ['Authorization' => 'Bearer '.$antigo])->assertUnauthorized();
    }

    // ---- 2.2 Aviso de compra aprovada ----

    public function test_approved_order_posts_the_purchase_to_the_product_url(): void
    {
        Http::fake([self::FLOW_URL => Http::response(['received' => true])]);
        $this->essencial->update(['purchase_webhook_url' => self::FLOW_URL]);
        $user = User::factory()->create(['name' => 'Maria Souza', 'email' => 'maria@example.com', 'phone' => '82999991234']);
        $order = $this->pedido($user, [[$this->essencial, 17, false]]);

        app(OrderFulfillment::class)->approve($order);
        $this->fimDaRequisicao();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::FLOW_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Accept', 'application/json')
            && $request->data() === [
                'telefone' => '5582999991234',
                'nome' => 'Maria Souza',
                'email' => 'maria@example.com',
                'produto' => 'treinamento-essencial',
                'produto_nome' => 'Treinamento Essencial',
                'valor' => '17,00',
                'pedido' => $order->id,
            ]);
    }

    public function test_order_bump_with_its_own_url_gets_its_own_notice_and_same_url_only_one(): void
    {
        Http::fake();
        $pac = Product::factory()->create(['slug' => 'pac', 'purchase_webhook_url' => 'https://knflow.test/api/flows/hook/pac']);
        $apostila = Product::factory()->create(['slug' => 'apostila', 'purchase_webhook_url' => self::FLOW_URL]);
        $this->essencial->update(['purchase_webhook_url' => self::FLOW_URL]);
        $order = $this->pedido(User::factory()->create(['phone' => '82999991234']), [
            [$this->essencial, 17, false], [$apostila, 9, true], [$pac, 12, true],
        ]);

        app(OrderFulfillment::class)->approve($order);
        $this->fimDaRequisicao();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->url() === self::FLOW_URL && $request['produto'] === 'treinamento-essencial');
        Http::assertSent(fn (Request $request) => $request->url() === 'https://knflow.test/api/flows/hook/pac' && $request['valor'] === '12,00');
    }

    public function test_product_without_url_sends_nothing(): void
    {
        Http::fake();
        app(OrderFulfillment::class)->approve($this->pedido(User::factory()->create(['phone' => '82999991234']), [[$this->essencial, 17, false]]));
        $this->fimDaRequisicao();

        Http::assertNothingSent();
    }

    public function test_buyer_without_phone_sends_nothing(): void
    {
        Http::fake();
        $this->essencial->update(['purchase_webhook_url' => self::FLOW_URL]);
        app(OrderFulfillment::class)->approve($this->pedido(User::factory()->create(['phone' => null]), [[$this->essencial, 17, false]]));
        $this->fimDaRequisicao();

        Http::assertNothingSent();
    }

    public function test_notice_goes_once_and_a_failure_does_not_block_the_access(): void
    {
        Http::fake([self::FLOW_URL => Http::response('erro', 500)]);
        $course = Course::factory()->create();
        $this->essencial->courses()->attach($course);
        $this->essencial->update(['purchase_webhook_url' => self::FLOW_URL]);
        $user = User::factory()->create(['phone' => '82999991234']);
        $order = $this->pedido($user, [[$this->essencial, 17, false]]);

        $this->assertTrue(app(OrderFulfillment::class)->approve($order));
        $this->assertFalse(app(OrderFulfillment::class)->approve($order->fresh()));
        $this->fimDaRequisicao();

        Http::assertSentCount(1);
        $this->assertTrue($user->fresh()->canAccessCourse($course));
    }

    // ---- Admin ----

    public function test_admin_saves_the_notice_url_and_sees_the_api_token(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.products.edit', $this->essencial))->assertOk()->assertSee('URL de aviso de compra');

        $this->actingAs($admin)->put(route('admin.products.update', $this->essencial), [
            'name' => $this->essencial->name,
            'slug' => $this->essencial->slug,
            'price' => '17,00',
            'course_ids' => [Course::factory()->create()->id],
            'purchase_webhook_url' => ' '.self::FLOW_URL.' ',
        ])->assertSessionHasNoErrors();

        $this->assertSame(self::FLOW_URL, $this->essencial->fresh()->purchase_webhook_url);

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Authorization: Bearer '.$this->token)
            ->assertSee(route('api.funil.comprou'));
    }

    public function test_notice_url_must_be_a_web_address(): void
    {
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $this->essencial), [
            'name' => $this->essencial->name,
            'slug' => $this->essencial->slug,
            'price' => '17,00',
            'course_ids' => [Course::factory()->create()->id],
            'purchase_webhook_url' => 'ftp://nao-serve',
        ])->assertSessionHasErrors('purchase_webhook_url');
    }

    /**
     * The notice runs after the response, when the server finishes the request.
     */
    private function fimDaRequisicao(): void
    {
        $this->app->terminate();
    }

    /**
     * @param  array<string, string>  $query
     */
    private function consultar(array $query, string $produto = 'treinamento-essencial'): TestResponse
    {
        return $this->getJson(route('api.funil.comprou', ['produto' => $produto, ...$query]), ['Authorization' => 'Bearer '.$this->token]);
    }

    /**
     * @param  list<array{0: Product, 1: float, 2: bool}>  $itens
     */
    private function pedido(User $user, array $itens): Order
    {
        $order = Order::factory()->for($user)->pending()->create(['product_id' => $itens[0][0]->id, 'amount' => collect($itens)->sum(1)]);

        foreach ($itens as [$product, $price, $bump]) {
            $order->items()->create(['product_id' => $product->id, 'description' => $product->name, 'unit_price' => $price, 'is_order_bump' => $bump]);
        }

        return $order;
    }
}
