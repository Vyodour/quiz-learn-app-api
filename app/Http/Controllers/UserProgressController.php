<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContentUnitOrder;
use App\Models\UserUnitProgress;
use App\Models\UserModuleEnrollment;
use App\Models\QuizInformation;
use App\Models\CodeChallenge;
use App\Models\UserQuizAttempt;
use App\Models\QuizQuestion;
use App\Models\UserQuizAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use App\Models\Module;
use App\Models\UserCodeSubmission;
use App\Models\Content;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Http\Resources\UserDashboardResource;

class UserProgressController extends Controller
{
    public function __construct ()
    {
        $this->middleware('auth:sanctum');
    }

    private function calculateModuleProgressPercentage(Module $module, int $userId): int
    {
        $totalUnits = ContentUnitOrder::whereHas('content', function ($query) use ($module) {
            $query->where('module_id', $module->id);
        })->count();

        if ($totalUnits === 0) {
            return 100;
        }

        $completedUnits = UserUnitProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->whereHas('unitOrder', function ($query) use ($module) {
                 $query->whereHas('content', function ($q) use ($module) {
                     $q->where('module_id', $module->id);
                 });
            })
            ->count();

        return min(100, round(($completedUnits / $totalUnits) * 100));
    }

    public function getDashboardStats(): JsonResponse
    {
        $user = Auth::user();

        try {
            $totalUnits = ContentUnitOrder::count();

            $completedUnitsCount = $user->userProgresses()
                ->where('is_completed', true)
                ->count();

            $overallCompletionPercentage = ($totalUnits > 0)
                ? round(($completedUnitsCount / $totalUnits) * 100)
                : 0;

            $nextUnit = ContentUnitOrder::orderBy('order_number')
                ->whereDoesntHave('userProgresses', function ($query) use ($user) {
                    $query->where('user_id', $user->id)->where('is_completed', true);
                })
                ->with('orderedUnit')
                ->first();

            $totalSubmissions = UserCodeSubmission::where('user_id', $user->id)->count();
            $passedSubmissions = UserCodeSubmission::where('user_id', $user->id)
                ->where('is_passed', true)
                ->count();

            $challengePassRate = ($totalSubmissions > 0)
                ? round(($passedSubmissions / $totalSubmissions) * 100)
                : 0;

            $averageQuizScore = DB::table('user_quiz_attempts')
                ->where('user_id', $user->id)
                ->avg('score') ?? 0;
            $averageQuizScore = round($averageQuizScore, 0);

            $enrolledModuleIds = UserModuleEnrollment::where('user_id', $user->id)->pluck('module_id');

            $moduleProgress = Module::whereIn('id', $enrolledModuleIds)
                ->with(['contents.orderedUnits.userProgresses' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])
                ->get()
                ->map(function ($module) {
                    $totalUnits = $module->contents->flatMap(fn ($content) => $content->orderedUnits)->count();
                    $completedUnits = $module->contents->flatMap(fn ($content) => $content->orderedUnits)
                        ->filter(fn ($unit) => $unit->userProgresses->isNotEmpty() && $unit->userProgresses->first()->is_completed)
                        ->count();

                    $completionPercentage = ($totalUnits > 0)
                        ? round(($completedUnits / $totalUnits) * 100)
                        : 0;

                    return [
                        'module_id' => $module->id,
                        'title' => $module->title,
                        'completion_percentage' => $completionPercentage,
                        'total_units' => $totalUnits,
                    ];
                });

            $dashboardStats = [
                'overall_completion_percentage' => $overallCompletionPercentage,
                'completed_units_count' => $completedUnitsCount,
                'total_units_count' => $totalUnits,
                'next_unit' => $nextUnit,
                'code_challenge_pass_rate' => $challengePassRate,
                'average_quiz_score' => $averageQuizScore,
                'module_progress' => $moduleProgress,
            ];

            return ResponseHelper::success(
                'Dashboard statistics fetched successfully.',
                new UserDashboardResource($dashboardStats),
                'dashboard_stats'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch dashboard statistics. Error: ' . $e->getMessage(), 500);
        }
    }

   public function submitQuiz(Request $request, ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();
        $progressPercentage = 0;

        try {
            if ($contentUnitOrder->ordered_unit_type !== QuizInformation::class) {
                return ResponseHelper::error('This unit is not a quiz and cannot accept batch answers.', 400);
            }

            if (!$contentUnitOrder->canBeAccessedByUser($user) || !$contentUnitOrder->isPreviousUnitCompleted($user)) {
                return ResponseHelper::error('Unit cannot be accessed!', 403);
            }

            $request->validate([
                'answers' => ['required', 'array', 'min:1'],
                'answers.*.quiz_question_id' => ['required', 'exists:quiz_questions,id'],
                'answers.*.submitted_option_index' => ['required', 'integer', 'min:0'],
            ]);

            $quizInfo = $contentUnitOrder->orderedUnit;
            if (!$quizInfo) {
                return ResponseHelper::error('Quiz content not found.', 404);
            }

            $submittedAnswers = $request->input('answers');
            $totalQuestions = $quizInfo->quizQuestions()->count();
            $correctAnswersCount = 0;
            $userAnswersToInsert = [];

            if (count($submittedAnswers) != $totalQuestions) {
                 return ResponseHelper::error('Please answers all ' . $totalQuestions . ' quiz questions.', 400);
            }

            foreach ($submittedAnswers as $answerData) {
                $questionId = $answerData['quiz_question_id'];
                $submittedIndex = $answerData['submitted_option_index'];

                $question = QuizQuestion::find($questionId);

                if (!$question || $question->quiz_information_id !== $quizInfo->id) {
                    continue;
                }

                $isCorrect = ($question->correct_option_index == $submittedIndex);
                if ($isCorrect) {
                    $correctAnswersCount++;
                }

                $userAnswersToInsert[] = [
                    'user_id' => $user->id,
                    'quiz_question_id' => $questionId,
                    'submitted_option_index' => $submittedIndex,
                    'is_correct' => $isCorrect,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $scorePercentage = $totalQuestions > 0 ? ($correctAnswersCount / $totalQuestions) * 100 : 0;
            $isPassed = ($scorePercentage >= $quizInfo->passing_score);

            $resultMessage = $isPassed
                ? 'Quiz finished and **PASSED** with score ' . round($scorePercentage) . '%!'
                : 'Quiz finished, but **FAILED** with score ' . round($scorePercentage) . '% (Needed ' . $quizInfo->passing_score . '%).';

            DB::transaction(function () use ($user, $contentUnitOrder, $quizInfo, $scorePercentage, $isPassed, $userAnswersToInsert, &$progressPercentage) {

                $questionIds = collect($userAnswersToInsert)->pluck('quiz_question_id');
                UserQuizAnswer::where('user_id', $user->id)
                    ->whereIn('quiz_question_id', $questionIds)
                    ->delete();
                UserQuizAnswer::insert($userAnswersToInsert);

                UserQuizAttempt::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'quiz_information_id' => $quizInfo->id,
                    ],
                    [
                        'score' => round($scorePercentage),
                        'is_passed' => $isPassed,
                        'submitted_answers' => json_encode(collect($userAnswersToInsert)->map(function($item) {
                            return [
                                'question_id' => $item['quiz_question_id'],
                                'submitted_index' => $item['submitted_option_index']
                            ];
                        })),
                        'attempted_at' => now(),
                    ]
                );

                if ($isPassed) {
                    UserUnitProgress::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'content_unit_order_id' => $contentUnitOrder->id,
                        ],
                        [
                            'is_completed' => true,
                            'completed_at' => now(),
                        ]
                    );
                }

                $module = $contentUnitOrder->content?->module;
                if ($module) {
                    $enrollment = UserModuleEnrollment::where('user_id', $user->id)
                        ->where('module_id', $module->id)
                        ->first();
                    if ($enrollment) {
                        $progressPercentage = $this->calculateModuleProgressPercentage($module, $user->id);
                        $enrollment->progress_percentage = $progressPercentage;
                        if ($progressPercentage === 100 && is_null($enrollment->completion_date)) {
                            $enrollment->completion_date = now();
                        }
                        $enrollment->save();
                    }
                }
            });

            return ResponseHelper::success(
                $resultMessage,
                [
                    'unit_id' => $contentUnitOrder->id,
                    'is_completed' => $isPassed,
                    'score_percentage' => round($scorePercentage, 2),
                    'correct_count' => $correctAnswersCount,
                    'total_questions' => $totalQuestions,
                    'module_progress_percentage' => $progressPercentage,
                ],
                'quiz_submitted'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to submit quiz. Error: ' . $e->getMessage(), 500);
        }
    }

    public function completeUnit(ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();
        $progressPercentage = 0;

        try {
            if (!$contentUnitOrder->canBeAccessedByUser($user) || !$contentUnitOrder->isPreviousUnitCompleted($user)) {
                 return ResponseHelper::error('Unit cannot be accessed!', 403);
            }

            $unitType = $contentUnitOrder->ordered_unit_type;

           if ($unitType === QuizInformation::class) {
                $quizInfo = $contentUnitOrder->orderedUnit;
                if ($quizInfo) {
                    $lastAttempt = UserQuizAttempt::where('user_id', $user->id)
                                ->where('quiz_information_id', $quizInfo->id)
                                ->latest()
                                ->first();

                    if (!$lastAttempt || !$lastAttempt->is_passed) {
                         return ResponseHelper::error('This is a quiz. Please submit all your answers and pass the quiz first.', 400);
                    }
                }
            }
            if ($unitType === CodeChallenge::class) {
                 $challenge = $contentUnitOrder->orderedUnit;
                 if ($challenge) {
                    $lastSubmission = UserCodeSubmission::where('user_id', $user->id)
                                    ->where('code_challenge_id', $challenge->id)
                                    ->where('is_passed', true)
                                    ->latest()
                                    ->first();

                    if (!$lastSubmission) {
                        return ResponseHelper::error('This is a code challenge. Please send your submission and pass all tests first.', 400);
                    }
                 }
            }

            DB::transaction(function () use ($user, $contentUnitOrder, &$progressPercentage) {
                UserUnitProgress::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'content_unit_order_id' => $contentUnitOrder->id,
                    ],
                    [
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]
                );

                $module = $contentUnitOrder->content?->module;

                if(!$module) {
                    throw new \Exception('Module data can not be retrieved.');
                }

                $enrollment = UserModuleEnrollment::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->first();

                if (!$enrollment) {
                    throw new \Exception('User is not enrolled in this module.');
                }

                $progressPercentage = $this->calculateModuleProgressPercentage($module, $user->id);

                $enrollment->progress_percentage = $progressPercentage;

                if ($progressPercentage === 100 && is_null($enrollment->completion_date)) {
                    $enrollment->completion_date = now();
                }

                $enrollment->save();
            });

            return ResponseHelper::success('Unit has been finished!', [
                'unit_id' => $contentUnitOrder->id,
                'user_id' => $user->id,
                'is_completed' => true,
                'module_progress_percentage' => $progressPercentage,
            ], 'progress_updated');

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to finish the unit. Error: ' . $e->getMessage(), 500);
        }
    }

    public function resetUnit(ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();
        $progressPercentage = 0;

        try {
            DB::transaction(function () use ($user, $contentUnitOrder, &$progressPercentage) {
                $deleted = UserUnitProgress::where('user_id', $user->id)
                    ->where('content_unit_order_id', $contentUnitOrder->id)
                    ->delete();

                if ($deleted) {
                    if ($contentUnitOrder->ordered_unit_type === QuizInformation::class) {
                        $quizInfo = $contentUnitOrder->orderedUnit;
                        if ($quizInfo) {
                            $questionIds = QuizQuestion::where('quiz_information_id', $quizInfo->id)->pluck('id');
                            UserQuizAnswer::where('user_id', $user->id)
                                ->whereIn('quiz_question_id', $questionIds)
                                ->delete();
                            UserQuizAttempt::where('user_id', $user->id)
                                ->where('quiz_information_id', $quizInfo->id)
                                ->delete();
                        }
                    }

                    $module = $contentUnitOrder->content?->module;

                    if (!$module) {
                         throw new \Exception('Module data could not be retrieved for reset.');
                    }

                    $enrollment = UserModuleEnrollment::where('user_id', $user->id)
                        ->where('module_id', $module->id)
                        ->first();

                    if (!$enrollment) {
                        return;
                    }

                    $progressPercentage = $this->calculateModuleProgressPercentage($module, $user->id);

                    $enrollment->progress_percentage = $progressPercentage;

                    if ($enrollment->completion_date && $progressPercentage < 100) {
                        $enrollment->completion_date = null;
                    }

                    $enrollment->save();
                } else {
                    throw new \Exception('Unit progress not found for reset.', 404);
                }
            });

            return ResponseHelper::success('Unit progress has been reset!', [
                'unit_id' => $contentUnitOrder->id,
                'user_id' => $user->id,
                'is_completed' => false,
                'module_progress_percentage' => $progressPercentage,
            ], 'progress_reset');

        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                 return ResponseHelper::error('Unit progress not found!', 404);
            }
            return ResponseHelper::error('Failed to reset unit progress. Error: ' . $e->getMessage(), 500);
        }
    }

    public function resetAllContentProgress(Request $request): JsonResponse
    {
        $request->validate([
            'content_id' => ['required', 'exists:contents,id'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $targetUserId = $request->input('user_id') ?? Auth::id();
        $contentId = $request->input('content_id');

        if (!$targetUserId) {
             return ResponseHelper::error('Target User ID is required!', 400);
        }

        try {
            $unitIds = ContentUnitOrder::where('content_id', $contentId)->pluck('id');

            if ($unitIds->isEmpty()) {
                return ResponseHelper::error('Content not found or has no units.', 404);
            }

            $content = \App\Models\Content::find($contentId);
            $moduleId = $content?->module_id;


            $deletedCount = DB::transaction(function () use ($targetUserId, $unitIds, $moduleId, $contentId) {
                 $count = UserUnitProgress::where('user_id', $targetUserId)
                     ->whereIn('content_unit_order_id', $unitIds)
                     ->delete();

                     $quizInfoIds = ContentUnitOrder::whereIn('id', $unitIds)
                                    ->where('ordered_unit_type', QuizInformation::class)
                                    ->pluck('ordered_unit_id');

                if ($quizInfoIds->isNotEmpty()) {
                    $questionIds = QuizQuestion::whereIn('quiz_information_id', $quizInfoIds)->pluck('id');
                    UserQuizAnswer::where('user_id', $targetUserId)
                        ->whereIn('quiz_question_id', $questionIds)
                        ->delete();

                    UserQuizAttempt::where('user_id', $targetUserId)
                        ->whereIn('quiz_information_id', $quizInfoIds)
                        ->delete();
                 }

                if ($moduleId) {
                    $module = Module::find($moduleId);
                    $enrollment = UserModuleEnrollment::where('user_id', $targetUserId)
                        ->where('module_id', $moduleId)
                        ->first();

                    if ($module && $enrollment) {
                        $progressPercentage = $this->calculateModuleProgressPercentage($module, $targetUserId);

                        $enrollment->progress_percentage = $progressPercentage;
                        if ($enrollment->completion_date && $progressPercentage < 100) {
                            $enrollment->completion_date = null;
                        }
                        $enrollment->save();
                    }
                }

                return $count;
            });

            return ResponseHelper::success(
                "{$deletedCount} progress records for Content ID: {$contentId} has been reset for User ID: {$targetUserId}. Module progress updated.",
                [
                    'content_id' => $contentId,
                    'user_id' => $targetUserId,
                    'records_deleted' => $deletedCount,
                ],
                'progress_reset_bulk'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to reset content progress in bulk. Error: ' . $e->getMessage(), 500);
        }
    }
}
