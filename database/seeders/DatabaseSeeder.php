<?php

namespace Database\Seeders;

use App\Enums\EnrollmentSource;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@julianabaloes.com.br')],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => env('ADMIN_PASSWORD', 'password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        // Pixels used by the old checkout; editable in Admin > Configurações.
        if (Setting::value('facebook_pixel_ids') === null) {
            Setting::store(['facebook_pixel_ids' => '2395963517103497,451180756322749']);
        }

        $this->call([
            CourseSeeder::class,
            PacEsculturasSeeder::class,
        ]);

        if (app()->environment('local')) {
            $student = User::query()->updateOrCreate(
                ['email' => 'aluno@teste.com'],
                [
                    'name' => 'Aluno Teste',
                    'password' => 'password',
                    'role' => UserRole::Student,
                    'email_verified_at' => now(),
                ],
            );

            Course::query()->each(fn (Course $course) => $student->enrollments()->firstOrCreate(
                ['course_id' => $course->id],
                ['source' => EnrollmentSource::Manual],
            ));
        }
    }
}
