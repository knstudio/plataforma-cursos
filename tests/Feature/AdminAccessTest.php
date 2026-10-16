<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['amount' => 97]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($order->user->email)
            ->assertSee('R$ 97,00');
    }

    public function test_student_cannot_open_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
