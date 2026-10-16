<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonAttachmentRequest;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class LessonAttachmentController extends Controller
{
    /**
     * Files go to the private "local" disk; students download them through
     * the authorized attachments.show route only.
     */
    public function store(StoreLessonAttachmentRequest $request, Lesson $lesson): RedirectResponse
    {
        $file = $request->file('file');

        $lesson->attachments()->create([
            'title' => $request->validated('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $file->store("attachments/{$lesson->id}", 'local'),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('admin.lessons.edit', $lesson)->with('status', 'Material anexado.');
    }

    public function destroy(LessonAttachment $attachment): RedirectResponse
    {
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return redirect()->route('admin.lessons.edit', $attachment->lesson_id)->with('status', 'Material removido.');
    }
}
