<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonCompletionController extends Controller
{
    /**
     * Mark the lesson as watched and move on to the next one.
     */
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($course, $lesson);

        $request->user()->completedLessons()->syncWithoutDetaching([
            $lesson->id => ['completed_at' => now()],
        ]);

        $lessons = $course->orderedLessons();
        $position = $lessons->search(fn (Lesson $item) => $item->id === $lesson->id);
        $nextLesson = $position === false ? null : $lessons->get($position + 1);

        if ($nextLesson) {
            return redirect()->route('courses.lessons.show', [$course, $nextLesson])
                ->with('status', 'Aula concluída! Bora para a próxima.');
        }

        return redirect()->route('courses.lessons.show', [$course, $lesson])
            ->with('status', 'Parabéns! Você concluiu a última aula do curso.');
    }

    public function destroy(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($course, $lesson);

        $request->user()->completedLessons()->detach($lesson->id);

        return redirect()->route('courses.lessons.show', [$course, $lesson]);
    }

    private function authorizeLesson(Course $course, Lesson $lesson): void
    {
        Gate::authorize('view', $course);

        abort_unless((int) $lesson->module()->value('course_id') === $course->id && $lesson->is_published, 404);
    }
}
