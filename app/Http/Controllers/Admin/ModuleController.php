<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModuleRequest;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;

class ModuleController extends Controller
{
    public function store(ModuleRequest $request, Course $course): RedirectResponse
    {
        $course->modules()->create([
            'title' => $request->validated('title'),
            'sort_order' => (int) $course->modules()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.courses.edit', $course)->with('status', 'Módulo criado.');
    }

    public function update(ModuleRequest $request, Module $module): RedirectResponse
    {
        $module->update($request->validated());

        return redirect()->route('admin.courses.edit', $module->course)->with('status', 'Módulo renomeado.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        if ($module->lessons()->exists()) {
            return back()->with('error', 'Mova ou exclua as aulas deste módulo antes de excluí-lo.');
        }

        $course = $module->course;
        $module->delete();

        return redirect()->route('admin.courses.edit', $course)->with('status', 'Módulo excluído.');
    }
}
