<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminStudentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_search_students(): void
    {
        User::factory()->create(['name' => 'Joana Balões', 'email' => 'joana@example.com']);
        User::factory()->create(['name' => 'Pedro Festas']);

        $this->actingAs($this->admin)->get(route('admin.students.index', ['q' => 'joana']))
            ->assertOk()
            ->assertSee('Joana Balões')
            ->assertDontSee('Pedro Festas');
    }

    public function test_student_without_password_receives_link_and_courses(): void
    {
        Notification::fake();
        $course = Course::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Maria Silva',
            'email' => 'MARIA@Example.com ',
            'course_ids' => [$course->id],
        ])->assertSessionHasNoErrors();

        $student = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame(UserRole::Student, $student->role);
        $this->assertTrue($student->canAccessCourse($course));
        Notification::assertSentTo($student, ResetPassword::class);
    }

    public function test_student_with_password_gets_no_email(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'senha-forte-123',
        ])->assertSessionHasNoErrors();

        $student = User::query()->where('email', 'ana@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('senha-forte-123', $student->password));
        Notification::assertNothingSent();
    }

    public function test_student_page_and_update(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.students.show', $student))->assertOk()->assertSee($student->email);

        $this->actingAs($this->admin)->put(route('admin.students.update', $student), [
            'name' => 'Nome Novo',
            'email' => 'novo@example.com',
            'role' => UserRole::Student->value,
        ])->assertRedirect(route('admin.students.show', $student));

        $this->assertSame('novo@example.com', $student->fresh()->email);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $this->actingAs($this->admin)->put(route('admin.students.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => UserRole::Student->value,
        ])->assertSessionHasErrors('role');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_admin_can_grant_access_with_expiration(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $expiresAt = now()->addMonth()->toDateString();

        $this->actingAs($this->admin)->post(route('admin.enrollments.store', $student), [
            'course_id' => $course->id,
            'expires_at' => $expiresAt,
        ])->assertRedirect(route('admin.students.show', $student));

        $enrollment = $student->enrollments()->firstOrFail();
        $this->assertSame($expiresAt, $enrollment->expires_at->toDateString());
        $this->assertTrue($student->canAccessCourse($course));
    }

    public function test_admin_can_revoke_and_restore_access(): void
    {
        $enrollment = Enrollment::factory()->create();
        $student = $enrollment->user;
        $course = $enrollment->course;

        $this->actingAs($this->admin)->patch(route('admin.enrollments.revoke', $enrollment));
        $this->assertFalse($student->canAccessCourse($course));

        $this->actingAs($this->admin)->patch(route('admin.enrollments.restore', $enrollment));
        $this->assertTrue($student->canAccessCourse($course));

        $this->actingAs($this->admin)->delete(route('admin.enrollments.destroy', $enrollment));
        $this->assertModelMissing($enrollment);
    }

    public function test_re_enrolling_clears_previous_revocation(): void
    {
        $enrollment = Enrollment::factory()->revoked()->create();

        $this->actingAs($this->admin)->post(route('admin.enrollments.store', $enrollment->user), [
            'course_id' => $enrollment->course_id,
        ]);

        $this->assertNull($enrollment->fresh()->revoked_at);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_admin_can_send_password_link(): void
    {
        Notification::fake();
        $student = User::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.students.password-link', $student))
            ->assertSessionHas('status');

        Notification::assertSentTo($student, ResetPassword::class);
    }
}
