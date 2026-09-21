<?php

use App\Http\Controllers\Admin\AnswerController;
use App\Http\Controllers\Admin\GradeQuizAttemptAnswerController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\GroupQuizController;
use App\Http\Controllers\Admin\GroupUserController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizAttemptController as AdminQuizAttemptController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\QuizImportController;
use App\Http\Controllers\Admin\SetCorrectAnswerController;
use App\Http\Controllers\User\QuizAttemptAnswerController;
use App\Http\Controllers\User\QuizAttemptController;
use App\Http\Controllers\User\QuizController as UserQuizController;
use App\Http\Controllers\User\SubmitQuizAttemptController;
use App\Http\Resources\User\CurrentUserResource;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn () => new CurrentUserResource(request()->user()))
    ->middleware('auth:sanctum');

Route::get('/quizzes', [UserQuizController::class, 'index'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/quizzes/{quiz}', [UserQuizController::class, 'show']);
    Route::get('/quizzes/{quiz}/attempt', [QuizAttemptController::class, 'show']);
    Route::post('/quizzes/{quiz}/attempt', [QuizAttemptController::class, 'store']);
    Route::put('/quizzes/{quiz}/attempt/answers/{questionUuid}', QuizAttemptAnswerController::class);
    Route::post('/quizzes/{quiz}/attempt/submit', SubmitQuizAttemptController::class);
});

Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'admin'])->scopeBindings()->group(function () {
    Route::apiResource('groups', GroupController::class);
    Route::get('groups/{group}/users', [GroupUserController::class, 'index'])->name('groups.users.index');
    Route::put('groups/{group}/users/{user}', [GroupUserController::class, 'store'])
        ->withoutScopedBindings()->name('groups.users.store');
    Route::delete('groups/{group}/users/{user}', [GroupUserController::class, 'destroy'])
        ->withoutScopedBindings()->name('groups.users.destroy');
    Route::get('groups/{group}/quizzes', [GroupQuizController::class, 'index'])->name('groups.quizzes.index');
    Route::put('groups/{group}/quizzes/{quiz}', [GroupQuizController::class, 'store'])
        ->withoutScopedBindings()->name('groups.quizzes.store');
    Route::delete('groups/{group}/quizzes/{quiz}', [GroupQuizController::class, 'destroy'])
        ->withoutScopedBindings()->name('groups.quizzes.destroy');
    Route::post('quizzes/import', QuizImportController::class)->name('quizzes.import');
    Route::get('quizzes/{quiz}/attempts', [AdminQuizAttemptController::class, 'index'])->name('quizzes.attempts.index');
    Route::get('quizzes/{quiz}/attempts/{attempt}', [AdminQuizAttemptController::class, 'show'])->name('quizzes.attempts.show');
    Route::put('quizzes/{quiz}/attempts/{attempt}/answers/{questionUuid}/grade', GradeQuizAttemptAnswerController::class)
        ->name('quizzes.attempts.answers.grade');
    Route::apiResource('quizzes', QuizController::class);
    Route::apiResource('quizzes.questions', QuestionController::class);
    Route::apiResource('quizzes.questions.answers', AnswerController::class);
    Route::put('quizzes/{quiz}/questions/{question}/correct-answer', SetCorrectAnswerController::class)
        ->name('quizzes.questions.correct-answer');
});
