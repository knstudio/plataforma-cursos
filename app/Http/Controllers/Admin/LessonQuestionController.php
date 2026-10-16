<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonQuestionController extends Controller
{
    private const FILTERS = [
        'pendentes' => 'Sem resposta',
        'respondidas' => 'Respondidas',
        'ocultas' => 'Ocultas',
        'todas' => 'Todas',
    ];

    public function index(Request $request): View
    {
        $filter = array_key_exists((string) $request->query('status'), self::FILTERS) ? $request->query('status') : 'pendentes';
        $courseId = $request->integer('curso') ?: null;

        $questions = LessonQuestion::query()
            ->with(['user', 'lesson.module.course'])
            ->withCount('answers')
            ->when($filter === 'pendentes', fn (Builder $query) => $query->unanswered())
            ->when($filter === 'respondidas', fn (Builder $query) => $query->whereNotNull('answered_at'))
            ->when($filter === 'ocultas', fn (Builder $query) => $query->where('is_hidden', true))
            ->when($courseId, fn (Builder $query) => $query->whereHas('lesson.module', fn (Builder $module) => $module->where('course_id', $courseId)))
            // Oldest first while waiting, so nobody is left behind; newest first otherwise.
            ->orderBy('created_at', $filter === 'pendentes' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            'filters' => self::FILTERS,
            'filter' => $filter,
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'courseId' => $courseId,
        ]);
    }

    public function show(LessonQuestion $question): View
    {
        $question->load(['user', 'lesson.module.course', 'answers.user']);

        return view('admin.questions.show', ['question' => $question]);
    }

    public function toggleVisibility(LessonQuestion $question): RedirectResponse
    {
        $question->update(['is_hidden' => ! $question->is_hidden]);

        return back()->with('status', $question->is_hidden ? 'Pergunta ocultada dos alunos.' : 'Pergunta visível para os alunos.');
    }

    public function destroy(LessonQuestion $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.questions.index')->with('status', 'Pergunta excluída.');
    }
}
