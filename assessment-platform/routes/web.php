<?php

use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\QuizSectionController;
use App\Http\Controllers\Admin\QuizSectionQuestionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('can:roles.manage')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::post('roles/{role}/clone', [RoleController::class, 'clone'])->name('roles.clone');
    });

    Route::middleware('can:users.view')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    });

    Route::middleware('can:quiz.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('quizzes', [QuizController::class, 'index'])->name('quizzes.index');
        Route::get('quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
        Route::post('quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::get('quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
        Route::delete('quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::post('quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('quizzes.publish');
        Route::post('quizzes/{quiz}/unpublish', [QuizController::class, 'unpublish'])->name('quizzes.unpublish');
        Route::post('quizzes/{quiz}/duplicate', [QuizController::class, 'duplicate'])->name('quizzes.duplicate');

        // Builder routes
        Route::get('quizzes/{quiz}/builder', [QuizController::class, 'builder'])->name('quizzes.builder');

        // Sections
        Route::post('quizzes/{quiz}/sections', [QuizSectionController::class, 'store'])->name('quizzes.sections.store');
        Route::put('quizzes/{quiz}/sections/{section}', [QuizSectionController::class, 'update'])->name('quizzes.sections.update');
        Route::delete('quizzes/{quiz}/sections/{section}', [QuizSectionController::class, 'destroy'])->name('quizzes.sections.destroy');
        Route::post('quizzes/{quiz}/sections/reorder', [QuizSectionController::class, 'reorder'])->name('quizzes.sections.reorder');

        // Section questions
        Route::get('quizzes/{quiz}/bank-search', [QuizSectionQuestionController::class, 'bankSearch'])->name('quizzes.bank-search');
        Route::post('quizzes/{quiz}/sections/{section}/questions', [QuizSectionQuestionController::class, 'attach'])->name('quizzes.sections.questions.attach');
        Route::delete('quizzes/{quiz}/sections/{section}/questions/{sectionQuestion}', [QuizSectionQuestionController::class, 'detach'])->name('quizzes.sections.questions.detach');
        Route::put('quizzes/{quiz}/sections/{section}/questions/{sectionQuestion}', [QuizSectionQuestionController::class, 'updatePivot'])->name('quizzes.sections.questions.update');
        Route::post('quizzes/{quiz}/sections/{section}/questions/reorder', [QuizSectionQuestionController::class, 'reorder'])->name('quizzes.sections.questions.reorder');

        // Inline question creation per type
        Route::post('quizzes/{quiz}/sections/{section}/questions/inline/single-select', [QuizSectionQuestionController::class, 'createInlineSingleSelect'])->name('quizzes.sections.questions.inline.single-select');
        Route::post('quizzes/{quiz}/sections/{section}/questions/inline/multi-select', [QuizSectionQuestionController::class, 'createInlineMultiSelect'])->name('quizzes.sections.questions.inline.multi-select');
        Route::post('quizzes/{quiz}/sections/{section}/questions/inline/coding', [QuizSectionQuestionController::class, 'createInlineCoding'])->name('quizzes.sections.questions.inline.coding');
        Route::post('quizzes/{quiz}/sections/{section}/questions/inline/rlhf', [QuizSectionQuestionController::class, 'createInlineRlhf'])->name('quizzes.sections.questions.inline.rlhf');
    });

    Route::middleware('can:questionbank.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('questions', [QuestionController::class, 'index'])->name('questions.index');
        Route::get('questions/create/{type}', [QuestionController::class, 'create'])->name('questions.create');
        Route::get('questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');

        Route::post('questions/single-select', [QuestionController::class, 'storeSingleSelect'])->name('questions.store.single-select');
        Route::post('questions/multi-select', [QuestionController::class, 'storeMultiSelect'])->name('questions.store.multi-select');
        Route::post('questions/coding', [QuestionController::class, 'storeCoding'])->name('questions.store.coding');
        Route::post('questions/rlhf', [QuestionController::class, 'storeRlhf'])->name('questions.store.rlhf');

        Route::put('questions/{question}/single-select', [QuestionController::class, 'updateSingleSelect'])->name('questions.update.single-select');
        Route::put('questions/{question}/multi-select', [QuestionController::class, 'updateMultiSelect'])->name('questions.update.multi-select');
        Route::put('questions/{question}/coding', [QuestionController::class, 'updateCoding'])->name('questions.update.coding');
        Route::put('questions/{question}/rlhf', [QuestionController::class, 'updateRlhf'])->name('questions.update.rlhf');
    });
});

// Public invitation routes (unauthenticated)
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}', [InvitationController::class, 'store'])->name('invitations.store');

require __DIR__.'/auth.php';
