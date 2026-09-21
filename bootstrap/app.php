<?php

use App\Exceptions\QuizAttempts\AnswerNotInQuestion;
use App\Exceptions\QuizAttempts\AttemptAlreadySubmitted;
use App\Exceptions\QuizAttempts\AttemptIncomplete;
use App\Exceptions\QuizAttempts\GradeVersionConflict;
use App\Exceptions\QuizAttempts\InvalidQuestionResponse;
use App\Exceptions\QuizAttempts\QuestionNotInAttempt;
use App\Exceptions\QuizAttempts\QuizAttemptException;
use App\Exceptions\QuizAttempts\QuizDeadlineExpired;
use App\Exceptions\QuizAttempts\QuizNotReady;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QuizAttemptException $exception, Request $request) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null;
            }

            if ($exception instanceof InvalidQuestionResponse) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => [$exception->field => [$exception->getMessage()]],
                ], 422);
            }

            [$status, $errors] = match ($exception::class) {
                QuizDeadlineExpired::class, QuizNotReady::class, AttemptAlreadySubmitted::class, GradeVersionConflict::class => [409, null],
                QuestionNotInAttempt::class => [422, ['question' => [$exception->getMessage()]]],
                AnswerNotInQuestion::class => [422, ['response.answer_uuid' => [$exception->getMessage()]]],
                AttemptIncomplete::class => [422, ['answers' => [$exception->getMessage()]]],
            };

            return response()->json(array_filter([
                'message' => $exception->getMessage(),
                'errors' => $errors,
            ]), $status);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
