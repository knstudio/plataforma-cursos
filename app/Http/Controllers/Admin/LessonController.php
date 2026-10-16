<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function create(Request $request, Course $course): View|RedirectResponse
    {
        $modules = $course->modules()->get();

        if ($modules->isEmpty()) {
            return redirect()->route('admin.courses.edit', $course)
                ->with('error', 'Crie um módulo antes de adicionar aulas.');
        }

        return view('admin.lessons.create', [
            'course' => $course,
            'modules' => $modules,
            'lesson' => new Lesson([
                'module_id' => $request->integer('module') ?: $modules->first()->id,
                'is_published' => true,
            ]),
        ]);
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        $attributes = $request->lessonAttributes();

        $lesson = Lesson::query()->create([
            ...$attributes,
            'sort_order' => (int) Lesson::query()->where('module_id', $attributes['module_id'])->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.lessons.edit', $lesson)
            ->with('status', 'Aula criada. Se quiser, anexe materiais abaixo.');
    }

    public function edit(Lesson $lesson): View
    {
        $lesson->load(['module.course', 'attachments']);
        $course = $lesson->module->course;

        return view('admin.lessons.edit', [
            'course' => $course,
            'modules' => $course->modules()->get(),
            'lesson' => $lesson,
        ]);
    }

    public function update(LessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $attributes = $request->lessonAttributes();

        if ($attributes['module_id'] !== (int) $lesson->module_id) {
            $attributes['sort_order'] = (int) Lesson::query()->where('module_id', $attributes['module_id'])->max('sort_order') + 1;
        }

        $lesson->update($attributes);

        return redirect()->route('admin.lessons.edit', $lesson)->with('status', 'Aula atualizada.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $course = $lesson->module->course;

        Storage::disk('local')->delete($lesson->attachments()->pluck('path')->all());
        $lesson->delete();

        return redirect()->route('admin.courses.edit', $course)->with('status', 'Aula excluída.');
    }
}
