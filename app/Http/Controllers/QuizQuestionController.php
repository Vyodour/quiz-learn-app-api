<?php
namespace App\Http\Controllers;

use App\Models\QuizInformation;
use App\Models\QuizQuestion;
use App\Http\Resources\QuizQuestionResource;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class QuizQuestionController extends Controller
{
    public function index(QuizInformation $quizInformation): JsonResponse
    {
        try {
            $questions = $quizInformation->questions()->get();

            return ResponseHelper::success(
                'Pertanyaan Quiz Berhasil Diambil.',
                QuizQuestionResource::collection($questions),
                'questions'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Gagal mengambil pertanyaan Quiz. Error: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, QuizInformation $quizInformation): JsonResponse
    {
        $validatedData = $request->validate([
            'question_text' => ['required', 'string', 'max:500'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:255'],
            'correct_option_index' => ['required', 'integer', 'min:0'],
        ]);

        if ($validatedData['correct_option_index'] >= count($validatedData['options'])) {
             return ResponseHelper::error('Indeks jawaban benar tidak valid.', 422);
        }

        try {
            $question = $quizInformation->questions()->create($validatedData);

            return ResponseHelper::success(
                'Pertanyaan berhasil ditambahkan.',
                new QuizQuestionResource($question),
                'question',
                201
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Gagal membuat pertanyaan. Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, QuizInformation $quizInformation, QuizQuestion $quizQuestion): JsonResponse
    {
        if ($quizQuestion->quiz_information_id !== $quizInformation->id) {
            return ResponseHelper::error('Pertanyaan tidak ditemukan dalam Quiz ini.', 404);
        }

        $validatedData = $request->validate([
            'question_text' => ['sometimes', 'required', 'string', 'max:500'],
            'options' => ['sometimes', 'required', 'array', 'min:2'],
            'options.*' => ['sometimes', 'required', 'string', 'max:255'],
            'correct_option_index' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        if ($request->has(['options', 'correct_option_index']) && $validatedData['correct_option_index'] >= count($validatedData['options'])) {
             return ResponseHelper::error('Indeks jawaban benar tidak valid.', 422);
        }
        if ($request->has('correct_option_index') && !$request->has('options')) {
            if ($validatedData['correct_option_index'] >= count($quizQuestion->options)) {
                return ResponseHelper::error('Indeks jawaban benar melebihi jumlah opsi yang ada.', 422);
            }
        }


        try {
            $quizQuestion->update($validatedData);

            return ResponseHelper::success(
                'Pertanyaan berhasil diperbarui.',
                new QuizQuestionResource($quizQuestion),
                'question'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Gagal memperbarui pertanyaan. Error: ' . $e->getMessage(), 500);
        }
    }

public function updateOption(Request $request, QuizInformation $quizInformation, QuizQuestion $quizQuestion): JsonResponse
{
    if ($quizQuestion->quiz_information_id !== $quizInformation->id) {
        return ResponseHelper::error('Pertanyaan tidak ditemukan dalam Quiz ini.', 404);
    }

    $validated = $request->validate([
        'index' => ['required', 'integer', 'min:0'],
        'option' => ['required', 'string', 'max:255'],
    ]);

    $options = $quizQuestion->options;

    if (!isset($options[$validated['index']])) {
        return ResponseHelper::error('Index opsi tidak valid.', 422);
    }

    $options[$validated['index']] = $validated['option'];

    try {
        $quizQuestion->update(['options' => $options]);

        return ResponseHelper::success(
            'Opsi pertanyaan berhasil diperbarui.',
            new QuizQuestionResource($quizQuestion),
            'question'
        );
    } catch (Exception $e) {
        return ResponseHelper::error('Gagal memperbarui opsi. Error: ' . $e->getMessage(), 500);
    }
}

    public function destroy(QuizInformation $quizInformation, QuizQuestion $quizQuestion): JsonResponse
    {
        if ($quizQuestion->quiz_information_id !== $quizInformation->id) {
            return ResponseHelper::error('Pertanyaan tidak ditemukan dalam Quiz ini.', 404);
        }

        try {
            $quizQuestion->delete();
            return ResponseHelper::success('Pertanyaan berhasil dihapus.', null);

        } catch (Exception $e) {
            return ResponseHelper::error('Gagal menghapus pertanyaan. Error: ' . $e->getMessage(), 500);
        }
    }
}
