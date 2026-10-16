<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EnrollmentSource;
use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'document'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return BelongsToMany<Lesson, $this>
     */
    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_completions')
            ->withPivot('completed_at');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Courses the user can currently watch: every published course for admins,
     * otherwise only courses with an active enrollment.
     *
     * @return Builder<Course>
     */
    public function accessibleCourses(): Builder
    {
        $query = Course::query()->published();

        if ($this->isAdmin()) {
            return $query;
        }

        return $query->whereHas('enrollments', function (Builder $enrollments): void {
            $enrollments->where('user_id', $this->id)->active();
        });
    }

    /**
     * Grant (or re-grant) access to a course. Re-enrolling clears a previous
     * revocation and replaces the expiration date.
     */
    public function enrollIn(
        Course $course,
        EnrollmentSource $source = EnrollmentSource::Manual,
        ?CarbonInterface $expiresAt = null,
        ?Order $order = null,
    ): Enrollment {
        return $this->enrollments()->updateOrCreate(
            ['course_id' => $course->id],
            [
                'source' => $source,
                'order_id' => $order?->id,
                'expires_at' => $expiresAt,
                'revoked_at' => null,
            ],
        );
    }

    public function canAccessCourse(Course $course): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $course->is_published
            && $this->enrollments()->where('course_id', $course->id)->active()->exists();
    }
}
