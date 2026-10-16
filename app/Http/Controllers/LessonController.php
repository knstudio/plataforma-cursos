<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, Course $course, Lesson $lesson): View
    {
        Gate::authorize('view', $course);

        $lesson->loadMissing('module', 'attachments');

        abort_unless((int) $lesson->module->course_id === $course->id, 404);
        abort_unless($lesson->is_published || $request->user()->isAdmin(), 404);

        $modules = $course->modules()
            ->with(['lessons' => fn ($lessons) => $lessons->published()])
            ->get();

        $lessons = $modules->flatMap(fn ($module) => $module->lessons)->values();
        $position = $lessons->search(fn (Lesson $item) => $item->id === $lesson->id);

        $completedIds = $request->user()->completedLessons()
            ->whereIn('lessons.id', $lessons->pluck('id'))
            ->pluck('lessons.id');

        // Public Q&A: every student of the course sees the questions; admins also see hidden ones.
        $questions = $lesson->questions()
            ->unless($request->user()->isAdmin(), fn ($query) => $query->visible())
            ->with(['user', 'answers.user'])
            ->latest()
            ->paginate(10, pageName: 'perguntas')
            ->fragment('duvidas');

        return view('student.lesson', [
            'questions' => $questions,
            'course' => $course,
            'lesson' => $lesson,
            'modules' => $modules,
            'completedIds' => $completedIds,
            'lessonNumber' => $position === false ? null : $position + 1,
            'previousLesson' => $position === false || $position === 0 ? null : $lessons->get($position - 1),
            'nextLesson' => $position === false ? null : $lessons->get($position + 1),
            'isCompleted' => $completedIds->contains($lesson->id),
            'progress' => $lessons->isEmpty() ? 0 : (int) round($completedIds->count() / $lessons->count() * 100),
        ]);
    }
}
