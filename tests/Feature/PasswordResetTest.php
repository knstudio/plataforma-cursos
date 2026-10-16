<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_is_sent_to_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'ninguem@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                ->assertOk();

            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'nova-senha-123',
                'password_confirmation' => 'nova-senha-123',
            ])->assertRedirect(route('login'))->assertSessionHasNoErrors();

            return true;
        });

        $this->assertTrue(Hash::check('nova-senha-123', $user->fresh()->password));
    }
}
