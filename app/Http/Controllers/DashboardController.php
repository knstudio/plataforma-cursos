<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LessonCompletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * List the courses the student can watch, with their progress.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $courses = $user->accessibleCourses()
            ->withCount(['lessons' => fn (Builder $lessons) => $lessons->where('lessons.is_published', true)])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $completedByCourse = LessonCompletion::query()
            ->join('lessons', 'lessons.id', '=', 'lesson_completions.lesson_id')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('lesson_completions.user_id', $user->id)
            ->where('lessons.is_published', true)
            ->whereIn('modules.course_id', $courses->modelKeys())
            ->groupBy('modules.course_id')
            ->selectRaw('modules.course_id, count(*) as total')
            ->pluck('total', 'course_id');

        // Published courses the student doesn't have yet, so they can buy them.
        $otherCourses = Course::query()
            ->published()
            ->whereKeyNot($courses->modelKeys())
            ->with(['products' => fn (BelongsToMany $products) => $products->withCount('courses')])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->filter(fn (Course $course) => $course->purchaseUrl() !== null);

        return view('student.dashboard', [
            'courses' => $courses,
            'completedByCourse' => $completedByCourse,
            'otherCourses' => $otherCourses,
        ]);
    }
}
