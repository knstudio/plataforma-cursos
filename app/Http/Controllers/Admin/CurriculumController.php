<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CurriculumController extends Controller
{
    /**
     * Persist the order of modules and lessons coming from the drag-and-drop editor.
     * Lessons may move between modules of the same course.
     */
    public function update(ReorderCurriculumRequest $request, Course $course): JsonResponse
    {
        $payload = collect($request->validated('modules'));

        $moduleIds = $payload->pluck('id')->map(fn ($id) => (int) $id);
        $lessonIds = $payload->pluck('lessons')->flatten()->map(fn ($id) => (int) $id);

        $courseModuleIds = $course->modules()->pluck('id');
        $courseLessonIds = $course->lessons()->pluck('lessons.id');

        $sameModules = $moduleIds->sort()->values()->all() === $courseModuleIds->sort()->values()->all();
        $sameLessons = $lessonIds->sort()->values()->all() === $courseLessonIds->sort()->values()->all();

        if (! $sameModules || ! $sameLessons) {
            return response()->json([
                'message' => 'A estrutura enviada não confere com o curso. Recarregue a página e tente de novo.',
            ], 422);
        }

        DB::transaction(function () use ($payload): void {
            foreach ($payload->values() as $moduleIndex => $item) {
                Module::query()->whereKey($item['id'])->update(['sort_order' => $moduleIndex + 1]);

                foreach (array_values($item['lessons']) as $lessonIndex => $lessonId) {
                    Lesson::query()->whereKey($lessonId)->update([
                        'module_id' => $item['id'],
                        'sort_order' => $lessonIndex + 1,
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Ordem salva.']);
    }
}
