<?php

namespace App\Notifications;

use App\Models\LessonQuestionAnswer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the student the teacher answered their question.
 */
class LessonQuestionAnswered extends Notification
{
    use Queueable;

    public function __construct(public LessonQuestionAnswer $answer) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $question = $this->answer->question->loadMissing('lesson');

        return (new MailMessage)
            ->subject('Sua pergunta foi respondida!')
            ->greeting('Olá, '.str($notifiable->name)->before(' ')->ucfirst().'!')
            ->line('A Juliana respondeu sua pergunta na aula "'.$question->lesson->title.'":')
            ->line('"'.Str::limit($question->body, 200).'"')
            ->action('Ver a resposta', $question->url())
            ->salutation('Bons estudos!');
    }
}
