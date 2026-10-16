<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_is_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.edit'))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_user_can_update_name_and_phone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.update'), [
            'name' => 'Maria da Silva',
            'phone' => '(11) 99999-0000',
        ])->assertRedirect(route('account.edit'))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Maria da Silva', $user->name);
        $this->assertSame('(11) 99999-0000', $user->phone);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.update'), [
            'name' => $user->name,
            'current_password' => 'errada',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.update'), [
            'name' => $user->name,
            'current_password' => 'password',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nova-senha-123', $user->fresh()->password));
    }

    public function test_email_cannot_be_changed_through_account_form(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);

        $this->actingAs($user)->put(route('account.update'), [
            'name' => $user->name,
            'email' => 'outro@example.com',
        ]);

        $this->assertSame('original@example.com', $user->fresh()->email);
    }
}
