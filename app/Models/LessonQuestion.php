<?php

namespace App\Models;

use Database\Factories\LessonQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A student's question under a lesson. Questions are public to everyone with
 * access to the course, so one answer helps every student with the same doubt.
 */
#[Fillable(['lesson_id', 'user_id', 'body', 'is_hidden', 'answered_at'])]
class LessonQuestion extends Model
{
    /** @use HasFactory<LessonQuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LessonQuestionAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(LessonQuestionAnswer::class)->oldest();
    }

    /**
     * @param  Builder<LessonQuestion>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }

    /**
     * Questions still waiting for the teacher (hidden ones don't need an answer).
     *
     * @param  Builder<LessonQuestion>  $query
     */
    #[Scope]
    protected function unanswered(Builder $query): void
    {
        $query->whereNull('answered_at')->where('is_hidden', false);
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    /**
     * Link to the question on the lesson page.
     */
    public function url(): string
    {
        $this->loadMissing('lesson.module.course');

        return route('courses.lessons.show', [$this->lesson->module->course, $this->lesson]).'#pergunta-'.$this->id;
    }
}
