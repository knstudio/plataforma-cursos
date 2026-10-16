<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonQuestionAnswerRequest;
use App\Models\LessonQuestion;
use App\Models\LessonQuestionAnswer;
use App\Notifications\LessonQuestionAnswered;
use Illuminate\Http\RedirectResponse;

/**
 * Answers are given from the admin question page or right on the lesson page.
 */
class LessonQuestionAnswerController extends Controller
{
    public function store(StoreLessonQuestionAnswerRequest $request, LessonQuestion $question): RedirectResponse
    {
        $answer = $question->answers()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $question->update(['answered_at' => $question->answered_at ?? now()]);

        if ($question->user_id !== $request->user()->id) {
            $question->user->notify(new LessonQuestionAnswered($answer));
        }

        return redirect()->to(strtok(url()->previous(), '#').'#pergunta-'.$question->id)
            ->with('status', 'Resposta publicada e aluno avisado por e-mail.');
    }

    public function destroy(LessonQuestionAnswer $answer): RedirectResponse
    {
        $question = $answer->question;
        $answer->delete();

        if (! $question->answers()->exists()) {
            $question->update(['answered_at' => null]);
        }

        return back()->with('status', 'Resposta excluída.');
    }
}
