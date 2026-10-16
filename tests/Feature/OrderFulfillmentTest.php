<?php

namespace Tests\Feature;

use App\Enums\EnrollmentSource;
use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\AccessGranted;
use App\Services\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private OrderFulfillment $fulfillment;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->fulfillment = app(OrderFulfillment::class);
    }

    public function test_access_days_set_the_expiration(): void
    {
        $this->freezeTime();
        [$order, $course] = $this->orderFor(accessDays: 365);

        $this->fulfillment->approve($order);

        $enrollment = $order->user->enrollments()->firstOrFail();
        $this->assertSame(EnrollmentSource::Order, $enrollment->source);
        $this->assertSame($order->id, $enrollment->order_id);
        $this->assertSame(now()->addDays(365)->toDateString(), $enrollment->expires_at->toDateString());
    }

    public function test_lifetime_access_is_never_downgraded_by_a_shorter_purchase(): void
    {
        $student = User::factory()->create();
        [$lifetimeOrder, $course] = $this->orderFor(student: $student);
        $this->fulfillment->approve($lifetimeOrder);

        [$shortOrder] = $this->orderFor(student: $student, course: $course, accessDays: 30);
        $this->fulfillment->approve($shortOrder);

        $enrollment = $student->enrollments()->firstOrFail();
        $this->assertNull($enrollment->expires_at);
        $this->assertSame($lifetimeOrder->id, $enrollment->order_id);
    }

    public function test_refund_moves_access_to_another_approved_order_of_the_same_course(): void
    {
        $student = User::factory()->create();
        [$first, $course] = $this->orderFor(student: $student, accessDays: 30);
        $this->fulfillment->approve($first);

        [$second] = $this->orderFor(student: $student, course: $course);
        $this->fulfillment->approve($second);

        $this->fulfillment->reverse($second, OrderStatus::Refunded);

        $enrollment = $student->enrollments()->firstOrFail();
        $this->assertTrue($student->canAccessCourse($course));
        $this->assertSame($first->id, $enrollment->order_id);
        $this->assertNotNull($enrollment->expires_at);
    }

    public function test_manual_access_survives_a_refund(): void
    {
        $student = User::factory()->create();
        [$order, $course] = $this->orderFor(student: $student);
        $student->enrollIn($course, EnrollmentSource::Manual);

        $this->fulfillment->approve($order);
        $this->fulfillment->reverse($order, OrderStatus::Refunded);

        $this->assertTrue($student->canAccessCourse($course));
    }

    public function test_simultaneous_notifications_approve_and_email_only_once(): void
    {
        [$order] = $this->orderFor(accessDays: null);
        // Second notification loaded the order before the first one approved it.
        $staleCopy = Order::query()->findOrFail($order->id);

        $this->assertTrue($this->fulfillment->approve($order));
        $this->assertFalse($this->fulfillment->approve($staleCopy));

        Notification::assertSentToTimes($order->user, AccessGranted::class, 1);
        $this->assertSame(OrderStatus::Approved, $staleCopy->status);
    }

    public function test_mail_failure_does_not_undo_the_access(): void
    {
        [$order, $course] = $this->orderFor(accessDays: null);
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP fora do ar'));

        $this->assertTrue($this->fulfillment->approve($order));
        $this->assertTrue($order->user->canAccessCourse($course));
    }

    public function test_cancel_does_not_touch_an_approved_order(): void
    {
        [$order] = $this->orderFor();
        $this->fulfillment->approve($order);

        $this->fulfillment->cancel($order);

        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);
    }

    /**
     * @return array{0: Order, 1: Course}
     */
    private function orderFor(?User $student = null, ?Course $course = null, ?int $accessDays = null): array
    {
        $course ??= Course::factory()->create();
        $product = Product::factory()->create(['access_days' => $accessDays]);
        $product->courses()->attach($course);

        $order = Order::factory()->pending()->for($student ?? User::factory())->create(['product_id' => $product->id]);
        $order->items()->create(['product_id' => $product->id, 'description' => $product->name, 'unit_price' => $product->price]);

        return [$order, $course];
    }
}
