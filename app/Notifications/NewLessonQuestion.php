<?php

namespace App\Notifications;

use App\Models\LessonQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the admins a student asked something under a lesson.
 */
class NewLessonQuestion extends Notification
{
    use Queueable;

    public function __construct(public LessonQuestion $question) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $question = $this->question->loadMissing('user', 'lesson.module.course');

        return (new MailMessage)
            ->subject('Nova pergunta: '.$question->lesson->title)
            ->greeting('Nova pergunta de '.$question->user->name)
            ->line('Curso: '.$question->lesson->module->course->title)
            ->line('Aula: '.$question->lesson->title)
            ->line('"'.$question->body.'"')
            ->action('Responder', route('admin.questions.show', $question));
    }
}
