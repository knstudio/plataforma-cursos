<?php

namespace App\Models;

use App\Enums\VideoProvider;
use App\Support\Duration;
use App\Support\VideoUrl;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['module_id', 'title', 'video_provider', 'video_id', 'duration_seconds', 'content', 'is_published', 'sort_order'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'video_provider' => VideoProvider::class,
            'duration_seconds' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @return HasMany<LessonAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class);
    }

    /**
     * @return HasMany<LessonQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(LessonQuestion::class);
    }

    /**
     * @param  Builder<Lesson>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function hasVideo(): bool
    {
        return $this->video_provider !== null && filled($this->video_id);
    }

    public function embedUrl(): ?string
    {
        return $this->hasVideo() ? $this->video_provider->embedUrl($this->video_id) : null;
    }

    /**
     * Shareable URL of the video, used to prefill the admin form.
     */
    public function videoUrl(): ?string
    {
        return VideoUrl::toUrl($this->video_provider, $this->video_id);
    }

    /**
     * Duration formatted as mm:ss (or h:mm:ss for long lessons).
     */
    public function formattedDuration(): ?string
    {
        return Duration::format($this->duration_seconds);
    }
}
