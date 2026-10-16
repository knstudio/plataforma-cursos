<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonAttachmentController;
use App\Http\Controllers\LessonCompletionController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonQuestionController;
use App\Http\Controllers\Webhooks\HotmartWebhookController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('checkout/pedido/{order}', [CheckoutController::class, 'result'])->name('checkout.result');
Route::get('checkout/{product:slug}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('checkout/{product:slug}', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');
Route::scopeBindings()->group(function () {
    Route::get('checkout/{product:slug}/{offer:code}', [CheckoutController::class, 'show'])->name('checkout.offer.show');
    Route::post('checkout/{product:slug}/{offer:code}', [CheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('checkout.offer.store');
});

Route::post('webhooks/mercadopago', MercadoPagoWebhookController::class)->name('webhooks.mercadopago');
Route::post('webhooks/hotmart', HotmartWebhookController::class)->name('webhooks.hotmart');

Route::middleware('guest')->group(function () {
    Route::get('entrar', [LoginController::class, 'create'])->name('login');
    Route::post('entrar', [LoginController::class, 'store']);

    Route::get('esqueci-a-senha', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('esqueci-a-senha', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('redefinir-senha/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('redefinir-senha', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('sair', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('minha-conta', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('minha-conta', [AccountController::class, 'update'])->name('account.update');

    Route::get('cursos/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('cursos/{course}/aulas/{lesson}', [LessonController::class, 'show'])->name('courses.lessons.show');
    Route::post('cursos/{course}/aulas/{lesson}/concluir', [LessonCompletionController::class, 'store'])->name('courses.lessons.complete');
    Route::delete('cursos/{course}/aulas/{lesson}/concluir', [LessonCompletionController::class, 'destroy'])->name('courses.lessons.uncomplete');

    Route::post('cursos/{course}/aulas/{lesson}/perguntas', [LessonQuestionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('courses.lessons.questions.store');
    Route::delete('perguntas/{question}', [LessonQuestionController::class, 'destroy'])->name('questions.destroy');

    Route::get('anexos/{attachment}', [LessonAttachmentController::class, 'show'])->name('attachments.show');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('cursos', Admin\CourseController::class)
            ->except('show')
            ->parameters(['cursos' => 'course'])
            ->names('courses');

        Route::post('cursos/{course}/modulos', [Admin\ModuleController::class, 'store'])->name('modules.store');
        Route::put('modulos/{module}', [Admin\ModuleController::class, 'update'])->name('modules.update');
        Route::delete('modulos/{module}', [Admin\ModuleController::class, 'destroy'])->name('modules.destroy');
        Route::put('cursos/{course}/grade', [Admin\CurriculumController::class, 'update'])->name('courses.curriculum.update');

        Route::get('cursos/{course}/aulas/nova', [Admin\LessonController::class, 'create'])->name('lessons.create');
        Route::post('cursos/{course}/aulas', [Admin\LessonController::class, 'store'])->name('lessons.store');
        Route::get('aulas/{lesson}/editar', [Admin\LessonController::class, 'edit'])->name('lessons.edit');
        Route::put('aulas/{lesson}', [Admin\LessonController::class, 'update'])->name('lessons.update');
        Route::delete('aulas/{lesson}', [Admin\LessonController::class, 'destroy'])->name('lessons.destroy');

        Route::post('aulas/{lesson}/anexos', [Admin\LessonAttachmentController::class, 'store'])->name('attachments.store');
        Route::delete('anexos/{attachment}', [Admin\LessonAttachmentController::class, 'destroy'])->name('attachments.destroy');

        Route::resource('produtos', Admin\ProductController::class)
            ->except('show')
            ->parameters(['produtos' => 'product'])
            ->names('products');

        Route::get('produtos/{product}/ofertas/nova', [Admin\ProductOfferController::class, 'create'])->name('offers.create');
        Route::post('produtos/{product}/ofertas', [Admin\ProductOfferController::class, 'store'])->name('offers.store');
        Route::get('ofertas/{offer}/editar', [Admin\ProductOfferController::class, 'edit'])->name('offers.edit');
        Route::put('ofertas/{offer}', [Admin\ProductOfferController::class, 'update'])->name('offers.update');
        Route::delete('ofertas/{offer}', [Admin\ProductOfferController::class, 'destroy'])->name('offers.destroy');

        Route::get('alunos', [Admin\StudentController::class, 'index'])->name('students.index');
        Route::get('alunos/novo', [Admin\StudentController::class, 'create'])->name('students.create');
        Route::post('alunos', [Admin\StudentController::class, 'store'])->name('students.store');
        Route::get('alunos/{student}', [Admin\StudentController::class, 'show'])->name('students.show');
        Route::put('alunos/{student}', [Admin\StudentController::class, 'update'])->name('students.update');
        Route::post('alunos/{student}/link-senha', [Admin\StudentController::class, 'sendPasswordLink'])->name('students.password-link');

        Route::post('alunos/{student}/matriculas', [Admin\EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::patch('matriculas/{enrollment}/bloquear', [Admin\EnrollmentController::class, 'revoke'])->name('enrollments.revoke');
        Route::patch('matriculas/{enrollment}/restaurar', [Admin\EnrollmentController::class, 'restore'])->name('enrollments.restore');
        Route::delete('matriculas/{enrollment}', [Admin\EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        Route::get('perguntas', [Admin\LessonQuestionController::class, 'index'])->name('questions.index');
        Route::get('perguntas/{question}', [Admin\LessonQuestionController::class, 'show'])->name('questions.show');
        Route::patch('perguntas/{question}/visibilidade', [Admin\LessonQuestionController::class, 'toggleVisibility'])->name('questions.visibility');
        Route::delete('perguntas/{question}', [Admin\LessonQuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('perguntas/{question}/respostas', [Admin\LessonQuestionAnswerController::class, 'store'])->name('answers.store');
        Route::delete('respostas/{answer}', [Admin\LessonQuestionAnswerController::class, 'destroy'])->name('answers.destroy');

        Route::get('configuracoes', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('configuracoes', [Admin\SettingsController::class, 'update'])->name('settings.update');

        Route::get('pedidos', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('pedidos/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('pedidos/{order}/sincronizar', [Admin\OrderController::class, 'sync'])->name('orders.sync');
    });
});
