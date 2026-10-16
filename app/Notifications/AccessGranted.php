<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Sent when a payment is approved: lists the unlocked courses and gives the
 * buyer a link to create (or reset) their password.
 */
class AccessGranted extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $courses = $this->order->purchasedProducts()
            ->flatMap(fn (Product $product) => $product->courses)
            ->unique('id');

        $passwordUrl = route('password.reset', [
            'token' => Password::createToken($notifiable),
            'email' => $notifiable->email,
        ]);

        $message = (new MailMessage)
            ->subject('Seu acesso foi liberado!')
            ->greeting('Olá, '.str($notifiable->name)->before(' ')->ucfirst().'!')
            ->line('Seu pagamento foi aprovado e o acesso já está liberado:');

        foreach ($courses as $course) {
            $message->line('• '.$course->title);
        }

        return $message
            ->line('Para entrar, use o seu e-mail ('.$notifiable->email.'). Se é sua primeira compra, crie sua senha no botão abaixo:')
            ->action('Criar minha senha', $passwordUrl)
            ->line('Já tem senha? É só entrar em '.route('login'))
            ->salutation('Bons estudos!');
    }
}
