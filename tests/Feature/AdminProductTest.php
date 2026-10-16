<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_product_with_brazilian_price(): void
    {
        $courses = Course::factory()->count(2)->create();

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Combo Completo',
            'price' => '1.297,90',
            'hotmart_product_id' => ' 4565 ',
            'is_active' => '1',
            'course_ids' => $courses->modelKeys(),
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->firstOrFail();
        $this->assertSame('combo-completo', $product->slug);
        $this->assertSame('1297.90', $product->price);
        $this->assertSame('4565', $product->hotmart_product_id);
        $this->assertNull($product->access_days);
        $this->assertEqualsCanonicalizing($courses->modelKeys(), $product->courses()->pluck('courses.id')->all());
    }

    public function test_product_needs_at_least_one_course(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Vazio',
            'price' => '10',
        ])->assertSessionHasErrors('course_ids');
    }

    public function test_admin_can_update_product_courses(): void
    {
        $product = Product::factory()->create();
        $old = Course::factory()->create();
        $new = Course::factory()->create();
        $product->courses()->attach($old);

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => '97',
            'access_days' => '365',
            'course_ids' => [$new->id],
        ])->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertSame([$new->id], $product->courses()->pluck('courses.id')->all());
        $this->assertSame(365, $product->access_days);
        $this->assertFalse($product->is_active);
    }

    public function test_admin_can_configure_order_bump(): void
    {
        $product = Product::factory()->create();
        $bump = Product::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => '17,00',
            'is_active' => '1',
            'course_ids' => [$course->id],
            'order_bump_product_id' => $bump->id,
            'order_bump_price' => '12,00',
            'order_bump_headline' => 'Leve mais 21 vídeos!',
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame($bump->id, $product->order_bump_product_id);
        $this->assertSame('12.00', $product->order_bump_price);
        $this->assertTrue($product->offersOrderBump());
    }

    public function test_admin_can_upload_and_remove_checkout_banner(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $course = Course::factory()->create();
        $base = ['name' => $product->name, 'slug' => $product->slug, 'price' => '17', 'course_ids' => [$course->id]];

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            ...$base,
            'checkout_banner_upload' => UploadedFile::fake()->image('banner.jpg', 1000, 400),
            'checkout_timer_minutes' => '10',
        ])->assertSessionHasNoErrors();

        $product->refresh();
        Storage::disk('public')->assertExists($product->checkout_banner);
        $this->assertSame(10, $product->checkout_timer_minutes);
        $banner = $product->checkout_banner;

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            ...$base,
            'remove_checkout_banner' => '1',
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertNull($product->checkout_banner);
        $this->assertSame(0, $product->checkout_timer_minutes);
        Storage::disk('public')->assertMissing($banner);
    }

    public function test_product_cannot_be_its_own_order_bump(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => '17',
            'course_ids' => [Course::factory()->create()->id],
            'order_bump_product_id' => $product->id,
        ])->assertSessionHasErrors(['order_bump_product_id', 'order_bump_price']);
    }

    public function test_product_with_orders_cannot_be_deleted(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $order->product))
            ->assertSessionHas('error');

        $this->assertModelExists($order->product);
    }

    public function test_product_pages_render(): void
    {
        $product = Product::factory()->create(['name' => 'Pacote Esculturas']);
        $product->courses()->attach(Course::factory()->create());

        $this->actingAs($this->admin)->get(route('admin.products.index'))->assertOk()->assertSee('Pacote Esculturas');
        $this->actingAs($this->admin)->get(route('admin.products.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.products.edit', $product))->assertOk();
    }
}
