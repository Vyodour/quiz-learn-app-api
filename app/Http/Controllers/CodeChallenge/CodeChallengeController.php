<?php
namespace App\Http\Controllers\CodeChallenge;


use App\Http\Controllers\Controller;
use App\Models\CodeChallenge;
use App\Models\UserCodeSubmission;
use App\Services\PistonService;
use Illuminate\Http\Request;
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

    public function submit(Request $request, CodeChallenge $challenge)
    {
        $request->validate([
            'submitted_code' => ['required', 'string'],
        ]);

        $submittedCode = $request->input('submitted_code');
        $userId = Auth::id();

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

        $submission = UserCodeSubmission::create([
            'user_id' => $userId,
            'code_challenge_id' => $challenge->id,
            'submitted_code' => $submittedCode,
            'is_passed' => $isPassed,
            'score' => $score,
            'grading_log' => $gradingLog,
        ]);

        return response()->json([
            'success' => true,
            'submission_id' => $submission->id,
            'is_passed' => $isPassed,
            'score' => $score,
            'grading_details' => $gradingLog,
            'message' => $isPassed ? 'Challenge Finished! Final Score.' : 'Challenge Failed. Check Score.',
        ]);
    }
}
