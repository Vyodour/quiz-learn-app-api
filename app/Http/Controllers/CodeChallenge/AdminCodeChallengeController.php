<?php
namespace App\Http\Controllers\CodeChallenge;

use App\Http\Controllers\Controller;
use App\Models\CodeChallenge;
use App\Models\UserCodeSubmission;
use Illuminate\Http\Request;

class AdminCodeChallengeController extends Controller
{
    public function index()
    {
        $challenges = CodeChallenge::withCount('submissions')->get();
        return response()->json($challenges);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'instruction_body' => 'required|string',
            'initial_code' => 'nullable|string',
            'language' => 'required|in:python,javascript,php,java',
            'test_cases' => 'required|json',
            'passing_score' => 'required|integer|min:1',
        ]);

        $challenge = CodeChallenge::create($validated);

        return response()->json([
            'message' => 'Tantangan berhasil dibuat!',
            'challenge' => $challenge
        ], 201);
    }

    public function update(Request $request, CodeChallenge $challenge)
    {
        $validated = $request->validate([
            'instruction_body' => 'sometimes|required|string',
            'initial_code' => 'nullable|string',
            'language' => 'sometimes|required|in:python,javascript,php,java',
            'test_cases' => 'sometimes|required|json',
            'passing_score' => 'sometimes|required|integer|min:1',
        ]);

        $challenge->update($validated);

        return response()->json([
            'message' => 'Tantangan berhasil diperbarui!',
            'challenge' => $challenge
        ]);
    }

    public function destroy(CodeChallenge $challenge)
    {
        $challenge->delete();

        return response()->json([
            'message' => 'Tantangan berhasil dihapus.'
        ], 204);
    }

    public function allSubmissions()
    {
        $submissions = UserCodeSubmission::with(['user', 'codeChallenge'])
                        ->latest()
                        ->paginate(20);

        return response()->json($submissions);
    }

    public function showSubmission(UserCodeSubmission $submission)
    {
        $submission->load('user', 'codeChallenge');

        return response()->json($submission);
    }
}
