<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PistonService
{
    private const API_URL = 'https://emkc.org/api/v2/piston/execute';

    private function getPistonVersion(string $language): string
    {
        return match ($language) {
            'python' => '3.10.0',
            'javascript' => '18.15.0',
            'php' => '8.2.0',
            default => 'latest',
        };
    }

    public function createGraderScript(string $submittedCode, array $testCases, string $language): string
    {
        $testJson = json_encode($testCases);

        if ($language === 'python') {
            return <<<PYTHON
import json
import traceback

TESTS = json.loads('$testJson')

# --- START KODE PENGGUNA ---
$submittedCode
# --- END KODE PENGGUNA ---

RESULTS = []
all_passed = True
FUNCTION_NAME = 'solve'

for test in TESTS:
    input_str = test.get('input', '[]')
    expected_str = str(test.get('expected_output'))

    try:
        input_args = json.loads(input_str)
        if not isinstance(input_args, list):
            input_args = [input_args]
    except:
        input_args = [input_str]

    try:
        expected = json.loads(expected_str)
    except:
        expected = expected_str

    passed = False
    actual = None
    error = None

    try:
        func = globals().get(FUNCTION_NAME)

        if func is None:
            raise NameError(f"Fungsi '{FUNCTION_NAME}' tidak terdefinisi.")

        actual = func(*input_args)

        if actual == expected:
            passed = True
        else:
            all_passed = False

    except Exception as e:
        error = f"{type(e).__name__}: {str(e)}"
        all_passed = False

    RESULTS.append({
        "input": input_str,
        "expected": expected,
        "actual": actual if passed or actual is not None else None,
        "passed": passed,
        "error": error
    })

print("---GRADER_RESULTS---")
print(json.dumps({"is_passed": all_passed, "details": RESULTS}))

PYTHON;
        }

        return $submittedCode;
    }

    public function executeCode(string $language, string $submittedCode, array $testCases): array
    {
        $finalScript = $this->createGraderScript($submittedCode, $testCases, $language);

        $payload = [
            'language' => $language,
            'version' => $this->getPistonVersion($language),
            'files' => [
                ['name' => 'main.' . ($language === 'python' ? 'py' : $language), 'content' => $finalScript]
            ],
            'run_timeout' => 5000,
        ];

        try {
            $response = Http::post(self::API_URL, $payload);

            if ($response->successful()) {
                return $this->processPistonResponse($response->json());
            }

            return ['is_passed' => false, 'log' => 'Piston API Error: ' . $response->status(), 'details' => []];

        } catch (\Exception $e) {
            return ['is_passed' => false, 'log' => 'Network Error: ' . $e->getMessage(), 'details' => []];
        }
    }

    private function processPistonResponse(array $pistonResponse): array
    {
        $fullOutput = $pistonResponse['run']['stdout'] ?? '';
        $errorOutput = $pistonResponse['run']['stderr'] ?? null;
        $delimiter = '---GRADER_RESULTS---';

        if ($errorOutput) {
            return [
                'is_passed' => false,
                'log' => 'Runtime or Compilation Error.',
                'details' => ['system_error' => trim($errorOutput)],
            ];
        }

        if (($pos = strpos($fullOutput, $delimiter)) !== false) {
            $jsonResult = substr($fullOutput, $pos + strlen($delimiter));
            $graderResult = json_decode(trim($jsonResult), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($graderResult)) {
                return [
                    'is_passed' => $graderResult['is_passed'] ?? false,
                    'log' => 'Grading Successful',
                    'details' => $graderResult['details'] ?? $graderResult['tests'] ?? [],
                ];
            }
        }

        return [
            'is_passed' => false,
            'log' => 'Output Grader tidak valid atau tidak ditemukan.',
            'details' => ['raw_output' => $fullOutput],
        ];
    }
}
