<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuizInformation;
use App\Models\ContentUnitOrder;
use App\Services\QuizAttemptService;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Models\UserQuizAttempt;

class QuizController extends Controller
{
    protected $attemptService;

    public function __construct(QuizAttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }

    public function startQuiz(ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();

        if ($contentUnitOrder->ordered_unit_type !== QuizInformation::class) {
            return ResponseHelper::error('The requested unit is not a quiz.', 400);
        }

        $quizInfo = $contentUnitOrder->orderedUnit;

        if (!$quizInfo) {
            return ResponseHelper::error('Quiz information not found.', 404);
        }

        if (!$this->attemptService->isNewAttemptAllowed($user, $quizInfo)) {
            return ResponseHelper::error('You have already passed this quiz. New attempts are not allowed.', 403);
        }

        $newAttempt = $this->attemptService->startNewAttempt($user, $quizInfo);

        $questions = $quizInfo->questions()->get(['id', 'question_text', 'options']);

        return ResponseHelper::success(
            'New quiz attempt started. Please submit answers with the returned attempt_id.',
            [
                'unit_id' => $contentUnitOrder->id,
                'quiz_info_id' => $quizInfo->id,
                'attempt_id' => $newAttempt->id,
                'questions' => $questions,
                'status' => 'in_progress'
            ],
            'quiz_access_granted'
        );
    }

    public function submitQuiz(Request $request, ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'attempt_id' => 'required|integer|exists:user_quiz_attempts,id',
            'answers' => 'required|array',
            'answers.*.quiz_question_id' => 'required|integer',
            'answers.*.submitted_option_index' => 'required|integer',
        ]);

        $attemptId = $request->input('attempt_id');

        if ($contentUnitOrder->ordered_unit_type !== QuizInformation::class) {
            return ResponseHelper::error('The requested unit is not a quiz.', 400);
        }

        $quizInfo = $contentUnitOrder->orderedUnit;

        if (!$quizInfo) {
            return ResponseHelper::error('Quiz information not found.', 404);
        }

        $attempt = UserQuizAttempt::where('id', $attemptId)
                                    ->where('user_id', $user->id)
                                    ->first();

        if (!$attempt) {
             return ResponseHelper::error('Quiz attempt not found or does not belong to the user.', 404);
        }

        if (!$this->attemptService->isNewAttemptAllowed($user, $quizInfo)) {
            return ResponseHelper::error('You have already passed this quiz. Submission denied.', 403);
        }

        $submittedAnswers = $request->input('answers');

        try {
            $attempt = $this->attemptService->processQuizSubmission(
                $attemptId,
                $quizInfo,
                $submittedAnswers
            );

            return ResponseHelper::success(
                $attempt->is_passed ? 'Congratulations, you passed the quiz!' : 'You did not pass. Please try again.',
                [
                    'score' => $attempt->score,
                    'is_passed' => $attempt->is_passed,
                    'attempt_number' => $attempt->attempt_number,
                    'passing_score_required' => $quizInfo->passing_score ?? null
                ],
                $attempt->is_passed ? 'quiz_passed' : 'quiz_failed'
            );

        } catch (\Exception $e) {
            return ResponseHelper::error('An error occurred while processing the quiz: ' . $e->getMessage(), 500);
        }
    }
}
