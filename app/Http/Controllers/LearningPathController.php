<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use App\Http\Requests\StoreLearningPathRequest;
use App\Http\Requests\UpdateLearningPathRequest;
use App\Helpers\ResponseHelper;
use App\Http\Resources\LearningPathResource;
use App\Http\Resources\LearningPathShowResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;

class LearningPathController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $learningPaths = LearningPath::withCount('modules')->get();

            $resource = LearningPathResource::collection($learningPaths);

            return ResponseHelper::success(
                'Learning Path Fetched',
                $resource,
                'learning_paths'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed When Taking Learning Paths', 500);
        }
    }

    public function show(LearningPath $learningPath): JsonResponse
    {
        try {
            $learningPath->load('modules');

            $resource = new LearningPathShowResource($learningPath);

            return ResponseHelper::success(
                'Learning Path Detail Taken',
                $resource,
                'learning_path'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed To Show Learning Path', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:learning_paths,title'],
            'slug' => ['required', 'string', 'max:255', 'unique:learning_paths,slug'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'is_published' => ['boolean'],
        ]);

        try {
            $learningPath = LearningPath::create($validated);

            return ResponseHelper::success(
                'Learning Path Created',
                new LearningPathShowResource($learningPath),
                'learning_path',
                201
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed To Create Learning Path. Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, LearningPath $learningPath): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255', 'unique:learning_paths,title,' . $learningPath->id],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:learning_paths,slug,' . $learningPath->id],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        try {
            $learningPath->update($validated);
            $resource = new LearningPathShowResource($learningPath->fresh());

            return ResponseHelper::success(
                'Learning Path Updated Successfully',
                $resource,
                'learning_path'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed To Update Learning Path. Error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(LearningPath $learningPath): JsonResponse
    {
        try {
            $learningPath->delete();

            return ResponseHelper::success(
                'Learning Path Deleted.',
                null,
                'data',
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed To Delete Learning Path. Error: ' . $e->getMessage(), 500);
        }
    }
}
