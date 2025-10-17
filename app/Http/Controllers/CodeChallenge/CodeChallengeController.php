<?php
namespace App\Http\Controllers\CodeChallenge;


use App\Http\Controllers\Controller;
use App\Models\CodeChallenge;
use App\Models\ContentUnitOrder;
use App\Models\UserCodeSubmission;
use App\Models\UserUnitProgress;
use App\Services\PistonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CodeChallengeController extends Controller
{
    protected $pistonService;

    public function __construct(PistonService $pistonService)
    {
        $this->pistonService = $pistonService;
    }

    public function show(CodeChallenge $challenge)
    {
        return response()->json($challenge);
    }

    public function submit(Request $request, CodeChallenge $challenge): JsonResponse
    {
        $request->validate([
            'submitted_code' => ['required', 'string'],
        ]);

        $submittedCode = $request->input('submitted_code');
        $user = Auth::user();
        $userId = $user->id;

        $unitOrder = ContentUnitOrder::where('ordered_unit_type', CodeChallenge::class)
            ->where('ordered_unit_id', $challenge->id)
            ->first();
        if (!$unitOrder) {
            return response()->json(['success' => false, 'message' => 'Content unit not found!.'], 404);
        }

        if (!$unitOrder->canBeAccessedByUser($user) || !$unitOrder->isPreviousUnitCompleted($user)) {
             return response()->json([
                 'success' => false,
                 'message' => 'Access denied! you are not premium or finished the previous content.'
             ], 403);
        }

        $testCasesArray = $challenge->test_cases;

        if (is_string($testCasesArray)) {
            $testCasesArray = json_decode($testCasesArray, true);
        }

        if (!is_array($testCasesArray)) {
            $testCasesArray = [];
        }

        $results = $this->pistonService->executeCode(
            $challenge->language,
            $submittedCode,
            $testCasesArray
        );

        $isPassed = $results['is_passed'] ?? false;
        $score = $isPassed ? $challenge->passing_score : 0;
        $gradingLog = $results['details'] ?? $results['log'] ?? [];

        try {
            DB::transaction(function () use ($challenge, $submittedCode, $userId, $isPassed, $score, $gradingLog) {
                $submission = UserCodeSubmission::create([
                    'user_id' => $userId,
                    'code_challenge_id' => $challenge->id,
                    'submitted_code' => $submittedCode,
                    'is_passed' => $isPassed,
                    'score' => $score,
                    'grading_log' => $gradingLog,
                ]);

                if ($isPassed) {
                    $unitOrder = ContentUnitOrder::where('ordered_unit_type', CodeChallenge::class)
                        ->where('ordered_unit_id', $challenge->id)
                        ->first();

                    if ($unitOrder) {
                        UserUnitProgress::updateOrCreate(
                            [
                                'user_id' => $userId,
                                'content_unit_order_id' => $unitOrder->id,
                            ],
                            [
                                'is_completed' => true,
                                'completed_at' => now(),
                            ]
                        );
                    }
                }
                return $submission;
            });

            return response()->json([
                'success' => true,
                'is_passed' => $isPassed,
                'score' => $score,
                'grading_details' => $gradingLog,
                'message' => $isPassed ? 'Challenge passed!.' : 'Challenge Failed. Dont`t worry, try again.',
            ]);

        } catch (\Exception $e) {
             return response()->json([
                'success' => false,
                'message' => 'Submission failed due to server error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
