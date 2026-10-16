<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The teacher's answer to a lesson question.
 */
#[Fillable(['lesson_question_id', 'user_id', 'body'])]
class LessonQuestionAnswer extends Model
{
    /**
     * @return BelongsTo<LessonQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(LessonQuestion::class, 'lesson_question_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
