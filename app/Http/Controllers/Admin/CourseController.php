<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Models\LessonAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $courses = Course::query()
            ->withCount([
                'lessons',
                'enrollments as active_enrollments_count' => fn (Builder $enrollments) => $enrollments->active(),
            ])
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('admin.courses.index', [
            'courses' => $courses,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.courses.create', [
            'course' => new Course(['is_published' => false]),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = new Course($request->safe()->except(['cover', 'remove_cover']));
        $course->sort_order ??= 0;

        if ($request->hasFile('cover')) {
            $course->cover_image = $request->file('cover')->store('courses', 'public');
        }

        $course->save();

        return redirect()->route('admin.courses.edit', $course)
            ->with('status', 'Curso criado. Agora monte os módulos e as aulas.');
    }

    public function edit(Course $course): View
    {
        $course->load(['modules.lessons' => fn ($lessons) => $lessons->withCount('attachments')]);

        return view('admin.courses.edit', [
            'course' => $course,
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $course->fill($request->safe()->except(['cover', 'remove_cover']));
        $course->sort_order ??= 0;

        if ($request->hasFile('cover') || $request->validated('remove_cover')) {
            $this->deleteCover($course);
            $course->cover_image = $request->hasFile('cover')
                ? $request->file('cover')->store('courses', 'public')
                : null;
        }

        $course->save();

        return redirect()->route('admin.courses.edit', $course)->with('status', 'Curso atualizado.');
    }

    /**
     * Courses with students are never deleted: unpublishing keeps the history safe.
     */
    public function destroy(Course $course): RedirectResponse
    {
        if ($course->enrollments()->exists()) {
            return back()->with('error', 'Este curso tem alunos matriculados. Despublique-o em vez de excluir.');
        }

        $attachmentPaths = LessonAttachment::query()
            ->whereIn('lesson_id', $course->lessons()->select('lessons.id'))
            ->pluck('path');

        Storage::disk('local')->delete($attachmentPaths->all());
        $this->deleteCover($course);

        $course->delete();

        return redirect()->route('admin.courses.index')->with('status', 'Curso excluído.');
    }

    private function deleteCover(Course $course): void
    {
        if ($course->cover_image) {
            Storage::disk('public')->delete($course->cover_image);
        }
    }
}
