<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreLessonQuestionRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\User;
use App\Notifications\NewLessonQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class LessonQuestionController extends Controller
{
    public function store(StoreLessonQuestionRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('view', $course);

        abort_unless((int) $lesson->module()->value('course_id') === $course->id && $lesson->is_published, 404);

        $question = $lesson->questions()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        if (! $request->user()->isAdmin()) {
            Notification::send(User::query()->where('role', UserRole::Admin)->get(), new NewLessonQuestion($question));
        }

        return redirect()->to($question->url())
            ->with('status', 'Pergunta enviada! Você recebe um e-mail quando a Juliana responder.');
    }

    /**
     * Students may withdraw a question while it has no answer yet.
     */
    public function destroy(Request $request, LessonQuestion $question): RedirectResponse
    {
        abort_unless($question->user_id === $request->user()->id && ! $question->isAnswered(), 403);

        $url = $question->url();
        $question->delete();

        return redirect()->to(strtok($url, '#').'#duvidas')->with('status', 'Pergunta excluída.');
    }
}
