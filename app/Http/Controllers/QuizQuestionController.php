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
                'Quiz question taken.',
                QuizQuestionResource::collection($questions),
                'questions'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to take question. Error: ' . $e->getMessage(), 500);
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
             return ResponseHelper::error('Answer not validated!.', 422);
        }

        try {
            $question = $quizInformation->questions()->create($validatedData);

            return ResponseHelper::success(
                'Question has created.',
                new QuizQuestionResource($question),
                'question',
                201
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create question. Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, QuizInformation $quizInformation, QuizQuestion $quizQuestion): JsonResponse
    {
        if ($quizQuestion->quiz_information_id !== $quizInformation->id) {
            return ResponseHelper::error('Question not found!.', 404);
        }

        $validatedData = $request->validate([
            'question_text' => ['sometimes', 'required', 'string', 'max:500'],
            'options' => ['sometimes', 'required', 'array', 'min:2'],
            'options.*' => ['sometimes', 'required', 'string', 'max:255'],
            'correct_option_index' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        if ($request->has(['options', 'correct_option_index']) && $validatedData['correct_option_index'] >= count($validatedData['options'])) {
             return ResponseHelper::error('Answer not validated!.', 422);
        }
        if ($request->has('correct_option_index') && !$request->has('options')) {
            if ($validatedData['correct_option_index'] >= count($quizQuestion->options)) {
                return ResponseHelper::error('Answers are too many.Only 4 can be accepted!.', 422);
            }
        }


        try {
            $quizQuestion->update($validatedData);

            return ResponseHelper::success(
                'Question has been updated.',
                new QuizQuestionResource($quizQuestion),
                'question'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update question. Error: ' . $e->getMessage(), 500);
        }
    }

public function updateOption(Request $request, QuizInformation $quizInformation, QuizQuestion $quizQuestion): JsonResponse
{
    if ($quizQuestion->quiz_information_id !== $quizInformation->id) {
        return ResponseHelper::error('Question not found!.', 404);
    }

    $validated = $request->validate([
        'index' => ['required', 'integer', 'min:0'],
        'option' => ['required', 'string', 'max:255'],
    ]);

    $options = $quizQuestion->options;

    if (!isset($options[$validated['index']])) {
        return ResponseHelper::error('Option not validated!.', 422);
    }

    $options[$validated['index']] = $validated['option'];

    try {
        $quizQuestion->update(['options' => $options]);

        return ResponseHelper::success(
            'Option has beem updated.',
            new QuizQuestionResource($quizQuestion),
            'question'
        );
    } catch (Exception $e) {
        return ResponseHelper::error('Failed to update option!. Error: ' . $e->getMessage(), 500);
    }
}

    public function destroy(QuizInformation $quizInformation, QuizQuestion $quizQuestion): JsonResponse
    {
        if ($quizQuestion->quiz_information_id !== $quizInformation->id) {
            return ResponseHelper::error('Question not found!.', 404);
        }

        try {
            $quizQuestion->delete();
            return ResponseHelper::success('Question has been deleted.', null);

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete question!. Error: ' . $e->getMessage(), 500);
        }
    }
}
