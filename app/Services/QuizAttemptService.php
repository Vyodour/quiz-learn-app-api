<?php

namespace App\Services;

use App\Models\User;
use App\Models\QuizInformation;
use App\Models\UserQuizAttempt;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\QuizQuestion; // Import model QuizQuestion

class QuizAttemptService
{
    /**
     * Cek apakah upaya baru diizinkan. Upaya diizinkan selama user BELUM PERNAH lulus.
     */
    public function isNewAttemptAllowed(User $user, QuizInformation $quizInfo): bool
    {
        $successfulAttempt = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_information_id', $quizInfo->id)
            ->where('is_passed', true)
            ->exists();

        return !$successfulAttempt;
    }

    /**
     * Mencatat upaya baru dalam status 'In Progress' dan mengembalikan ID-nya.
     */
    public function startNewAttempt(User $user, QuizInformation $quizInfo): UserQuizAttempt
    {
        $latestAttemptNumber = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_information_id', $quizInfo->id)
            ->count();

        return UserQuizAttempt::create([
            'user_id' => $user->id,
            'quiz_information_id' => $quizInfo->id,
            'attempt_number' => $latestAttemptNumber + 1,
            'score' => 0,
            'is_passed' => false,
        ]);
    }

    /**
     * Memproses jawaban kuis dan MENGUPDATE upaya yang sudah ada.
     * @throws NotFoundHttpException Jika Attempt ID tidak ditemukan.
     * @throws \Exception Jika ada masalah integritas data.
     */
    public function processQuizSubmission(int $attemptId, QuizInformation $quizInfo, array $submittedAnswers): UserQuizAttempt
    {
        $attempt = UserQuizAttempt::find($attemptId);

        if (!$attempt) {
            throw new NotFoundHttpException("Quiz attempt with ID {$attemptId} not found.");
        }

        if ($attempt->is_passed === true || $attempt->submitted_answers !== null) {
             throw new \Exception("This quiz attempt has already been submitted.");
        }

        // Ambil semua pertanyaan yang sah untuk kuis ini
        // Kita juga mengambil 'correct_option_index' yang merupakan kunci jawaban
        $validQuestions = $quizInfo->questions()
                                    ->pluck('correct_option_index', 'id')
                                    ->toArray();

        // 1. Map submitted answers ke Question ID => Option Index
        $submissionMap = collect($submittedAnswers)->mapWithKeys(function ($answer) {
            return [intval($answer['quiz_question_id']) => $answer['submitted_option_index']];
        });

        // 2. VALIDASI INTEGRITAS: Pastikan semua pertanyaan yang disubmit milik kuis ini
        $submittedQuestionIds = $submissionMap->keys()->toArray();
        $validQuestionIds = array_keys($validQuestions);

        $invalidQuestions = array_diff($submittedQuestionIds, $validQuestionIds);

        if (!empty($invalidQuestions)) {
            // Ini menangani kasus "waktu kumasukan question id yang tidak sesuai dengan quiz information malah bisa jawab"
            throw new \Exception('One or more submitted question IDs are invalid for this quiz.');
        }


        // 3. LOGIKA PENILAIAN
        $totalQuestions = count($validQuestions);
        $correctCount = 0;

        foreach ($submissionMap as $questionId => $userAnswerIndex) {
            // Ambil indeks jawaban yang benar dari koleksi $validQuestions
            $correctAnswerIndex = $validQuestions[$questionId];

            // Ini menangani kasus "jawaban yang benar malah salah"
            // Pastikan perbandingan dilakukan antara integer
            if ($userAnswerIndex !== null && intval($userAnswerIndex) === intval($correctAnswerIndex)) {
                $correctCount++;
            }
        }

        $score = ($totalQuestions > 0) ? ($correctCount / $totalQuestions) * 100 : 0;
        $isPassed = $score >= $quizInfo->passing_score;

        // UPDATE record yang sudah ada
        $attempt->update([
            'score' => $score,
            'is_passed' => $isPassed,
            'submitted_answers' => $submittedAnswers,
        ]);

        return $attempt;
    }
}
