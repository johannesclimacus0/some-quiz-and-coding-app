<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\QuizAttemptDetailResource;
use App\Http\Resources\Admin\QuizAttemptSummaryResource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class QuizAttemptController extends Controller
{
    public function index(Quiz $quiz): AnonymousResourceCollection
    {
        Gate::authorize('view', $quiz);

        return QuizAttemptSummaryResource::collection(
            $quiz->attempts()->with(['user', 'quiz'])->latest('id')->paginate(18)
        );
    }

    public function show(Quiz $quiz, QuizAttempt $attempt): QuizAttemptDetailResource
    {
        Gate::authorize('view', $quiz);

        return new QuizAttemptDetailResource($attempt->load(['user', 'quiz', 'answers']));
    }
}
