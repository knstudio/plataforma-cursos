<?php

namespace App\Models;

use Database\Factories\LessonAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lesson_id', 'title', 'path', 'original_name', 'size'])]
class LessonAttachment extends Model
{
    /** @use HasFactory<LessonAttachmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
