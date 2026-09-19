<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Quizzes\ImportQuizzesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportQuizzesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class QuizImportController extends Controller
{
    public function __invoke(ImportQuizzesRequest $request, ImportQuizzesAction $action): JsonResponse
    {
        $file = $request->file('file');

        try {
            $result = $action->handle(
                contents: $file->getContent(),
                format: $file->getClientOriginalExtension(),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }

        return response()->json([
            'message' => 'Импорт завершён.',
            'data' => $result,
        ], 201);
    }
}
