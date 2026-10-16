<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class EnrollmentController extends Controller
{
    public function store(StoreEnrollmentRequest $request, User $student): RedirectResponse
    {
        $expiresAt = $request->validated('expires_at')
            ? Carbon::parse($request->validated('expires_at'))->endOfDay()
            : null;

        $student->enrollIn(
            Course::query()->findOrFail($request->validated('course_id')),
            EnrollmentSource::Manual,
            $expiresAt,
        );

        return redirect()->route('admin.students.show', $student)->with('status', 'Acesso liberado.');
    }

    public function revoke(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->update(['revoked_at' => now()]);

        return redirect()->route('admin.students.show', $enrollment->user_id)->with('status', 'Acesso bloqueado.');
    }

    public function restore(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->update(['revoked_at' => null]);

        return redirect()->route('admin.students.show', $enrollment->user_id)->with('status', 'Acesso restaurado.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $studentId = $enrollment->user_id;
        $enrollment->delete();

        return redirect()->route('admin.students.show', $studentId)->with('status', 'Matrícula removida.');
    }
}
