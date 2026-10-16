<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    /**
     * Send the student to the first lesson they haven't finished yet.
     */
    public function show(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('view', $course);

        $lessons = $course->orderedLessons();

        if ($lessons->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'Este curso ainda não tem aulas disponíveis.');
        }

        $completedIds = $request->user()->completedLessons()
            ->whereIn('lessons.id', $lessons->pluck('id'))
            ->pluck('lessons.id');

        $nextLesson = $lessons->first(fn (Lesson $lesson) => ! $completedIds->contains($lesson->id))
            ?? $lessons->first();

        return redirect()->route('courses.lessons.show', [$course, $nextLesson]);
    }
}
