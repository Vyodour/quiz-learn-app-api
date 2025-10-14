<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PistonService
{
    private const API_URL = 'https://emkc.org/api/v2/piston/execute';

    /**
     * Helper untuk mendapatkan versi runtime Piston.
     */
    private function getPistonVersion(string $language): string
    {
        return match ($language) {
            'python' => '3.10.0',
            'javascript' => '18.15.0',
            'php' => '8.2.0',
            default => 'latest',
        };
    }

    /**
     * Membuat Grader Script Wrapper (Python) yang menggabungkan kode pengguna dan test cases.
     * Logika ini harus diadaptasi jika Anda mendukung bahasa selain Python.
     */
    public function createGraderScript(string $submittedCode, array $testCases, string $language): string
    {
        $testJson = json_encode($testCases);

        if ($language === 'python') {
            // Kerangka Python Grader Script
            return <<<PYTHON
import json
import traceback

# Data Test Cases dari Laravel
TESTS = json.loads('$testJson')

# --- START KODE PENGGUNA ---
# Kode yang disubmit user akan di-insert di sini.
$submittedCode
# --- END KODE PENGGUNA ---

RESULTS = []
all_passed = True

# Asumsi: Kode pengguna mendefinisikan fungsi bernama 'solve' atau nama fungsi utama tantangan.
# Anda harus menyesuaikan 'solve' sesuai nama fungsi yang Anda minta.
FUNCTION_NAME = 'solve'

for test in TESTS:
    input_args = test.get('input', [])
    expected = test.get('expected')
    passed = False
    actual = None
    error = None

    try:
        # Panggil fungsi pengguna secara dinamis
        # Jika input_args adalah list, gunakan *input_args
        func = globals().get(FUNCTION_NAME)

        if func is None:
            raise NameError(f"Fungsi '{FUNCTION_NAME}' tidak terdefinisi.")

        # Eksekusi fungsi
        actual = func(*input_args)

        if actual == expected:
            passed = True
        else:
            all_passed = False

    except Exception as e:
        error = f"{type(e).__name__}: {str(e)}"
        all_passed = False

    RESULTS.append({
        "input": input_args,
        "expected": expected,
        "actual": actual if passed or actual is not None else None,
        "passed": passed,
        "error": error
    })

# Cetak hasil akhir dengan delimiter khusus agar Laravel mudah mem-parse
print("---GRADER_RESULTS---")
print(json.dumps({"is_passed": all_passed, "tests": RESULTS}))

PYTHON;
        }

        // Jika bahasa tidak didukung atau Anda tidak punya wrapper:
        return $submittedCode;
    }

    /**
     * Mengirim Grader Script ke Piston API.
     */
    public function executeCode(string $language, string $submittedCode, array $testCases): array
    {
        $finalScript = $this->createGraderScript($submittedCode, $testCases, $language);

        $payload = [
            'language' => $language,
            'version' => $this->getPistonVersion($language),
            'files' => [
                ['name' => 'main.' . ($language === 'python' ? 'py' : $language), 'content' => $finalScript]
            ],
            'run_timeout' => 5000, // 5 detik batas waktu eksekusi
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

    /**
     * Memproses respons dari Piston dan mengekstrak hasil penilaian.
     */
    private function processPistonResponse(array $pistonResponse): array
    {
        $fullOutput = $pistonResponse['run']['stdout'] ?? '';
        $errorOutput = $pistonResponse['run']['stderr'] ?? null;
        $delimiter = '---GRADER_RESULTS---';

        // 1. Cek Kompilasi/Runtime Error Piston (misalnya Time Limit Exceeded)
        if ($errorOutput) {
            return [
                'is_passed' => false,
                'log' => 'Runtime or Compilation Error.',
                'details' => ['system_error' => trim($errorOutput)],
            ];
        }

        // 2. Cari Delimiter Grader Script Anda
        if (($pos = strpos($fullOutput, $delimiter)) !== false) {
            $jsonResult = substr($fullOutput, $pos + strlen($delimiter));
            $graderResult = json_decode(trim($jsonResult), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($graderResult)) {
                // Berhasil: Ambil hasil penilaian dari Grader Script
                return [
                    'is_passed' => $graderResult['is_passed'] ?? false,
                    'log' => 'Grading Successful',
                    'details' => $graderResult['tests'] ?? [],
                ];
            }
        }

        // 3. Tangani output tidak terstruktur (misalnya: pengguna mencetak sesuatu sebelum delimiter)
        return [
            'is_passed' => false,
            'log' => 'Output Grader tidak valid atau tidak ditemukan.',
            'details' => ['raw_output' => $fullOutput],
        ];
    }
}
