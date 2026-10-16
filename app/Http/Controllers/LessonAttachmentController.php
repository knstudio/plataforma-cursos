<?php

namespace App\Http\Controllers;

use App\Models\LessonAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonAttachmentController extends Controller
{
    /**
     * Download a lesson file. Files live on the private disk, so this route is
     * the only way to reach them.
     */
    public function show(LessonAttachment $attachment): StreamedResponse
    {
        $attachment->loadMissing('lesson.module.course');

        Gate::authorize('view', $attachment->lesson->module->course);

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
