<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Whether the user may watch the course content.
     */
    public function view(User $user, Course $course): bool
    {
        return $user->canAccessCourse($course);
    }
}
