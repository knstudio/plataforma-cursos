<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentSource;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $students = User::query()
            ->withCount(['enrollments as active_enrollments_count' => fn (Builder $enrollments) => $enrollments->active()])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    /**
     * Without a password, the student receives a link to create their own.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $sendsPasswordLink = blank($request->validated('password'));

        $student = DB::transaction(function () use ($request): User {
            $student = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password') ?: Str::password(32),
                'role' => UserRole::Student,
            ]);

            Course::query()
                ->whereKey($request->validated('course_ids', []))
                ->each(fn (Course $course) => $student->enrollIn($course, EnrollmentSource::Manual));

            return $student;
        });

        if ($sendsPasswordLink) {
            Password::sendResetLink(['email' => $student->email]);
        }

        return redirect()->route('admin.students.show', $student)->with('status', $sendsPasswordLink
            ? 'Aluno cadastrado. Enviamos um e-mail para ele criar a senha.'
            : 'Aluno cadastrado.');
    }

    public function show(User $student): View
    {
        $student->load([
            'enrollments' => fn ($enrollments) => $enrollments->with('course:id,title,slug')->latest(),
            'orders' => fn ($orders) => $orders->with('product:id,name')->latest(),
        ]);

        return view('admin.students.show', [
            'student' => $student,
            'availableCourses' => Course::query()
                ->whereNotIn('id', $student->enrollments->pluck('course_id'))
                ->orderBy('title')
                ->get(['id', 'title']),
        ]);
    }

    public function update(UpdateStudentRequest $request, User $student): RedirectResponse
    {
        $student->fill($request->safe()->only(['name', 'email', 'phone', 'role']));

        if (filled($request->validated('password'))) {
            $student->password = $request->validated('password');
        }

        $student->save();

        return redirect()->route('admin.students.show', $student)->with('status', 'Dados do aluno atualizados.');
    }

    public function sendPasswordLink(User $student): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $student->email]);

        return back()->with($status === Password::RESET_LINK_SENT ? 'status' : 'error', __($status));
    }
}
