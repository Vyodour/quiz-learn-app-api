<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\LearningPath;
use App\Helpers\ResponseHelper;
use App\Http\Resources\ModuleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function index(LearningPath $learningPath): JsonResponse
    {
        try {
            $modules = $learningPath->modules()->orderBy('order_number')->get();

            return ResponseHelper::success(
                'Modules Fetched Successfully',
                ModuleResource::collection($modules),
                'modules'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to Fetch Modules. Error: ' . $e->getMessage(), 500);
        }
    }

    public function indexOrphan(): JsonResponse
    {
        try {
            $modules = Module::whereNull('learning_path_id')
                ->orderBy('order_number')
                ->get();

            return ResponseHelper::success(
                'Orphan Modules Fetched Successfully',
                ModuleResource::collection($modules),
                'modules'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to Fetch Orphan Modules. Error: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $learningPathId = $request->input('learning_path_id');

        $orderNumberUniqueRule = Rule::unique('modules', 'order_number')->where(function ($query) use ($learningPathId) {
            return $query->where('learning_path_id', $learningPathId);
        });

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:modules,slug'],
            'description' => ['nullable', 'string'],
            'duration' => ['required', 'integer', 'min:1'],
            'level' => ['required', 'in:simple,moderate,advance'],

            'learning_path_id' => ['nullable', 'exists:learning_paths,id'],

            'order_number' => [
                'sometimes',
                'integer',
                'min:1',
                $learningPathId ? $orderNumberUniqueRule : 'nullable'
            ],
        ]);

        try {
            if (!isset($validated['order_number'])) {
                $maxOrder = Module::where('learning_path_id', $learningPathId)->max('order_number');
                $validated['order_number'] = $maxOrder !== null ? $maxOrder + 1 : 1;
            }

            $module = Module::create($validated);

            return ResponseHelper::success(
                'Module Created Successfully',
                new ModuleResource($module),
                'module',
                201
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed To Create Module. Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Module $module): JsonResponse
    {
        $targetLearningPathId = $request->input('learning_path_id', $module->learning_path_id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('modules', 'slug')->ignore($module->id)
            ],
            'description' => ['nullable', 'string'],
            'duration' => ['sometimes', 'integer', 'min:1'],
            'level' => ['sometimes', 'in:simple,moderate,advance'],
            'rating' => ['sometimes', 'numeric', 'min:0', 'max:5'],

            'learning_path_id' => ['sometimes', 'nullable', 'exists:learning_paths,id'],

            'order_number' => ['sometimes', 'integer', 'min:1',
                Rule::unique('modules', 'order_number')
                    ->ignore($module->id)
                    ->where(function ($query) use ($targetLearningPathId) {
                        return $query->where('learning_path_id', $targetLearningPathId);
                    })
            ],
        ]);

        try {
            $module->update($validated);

            return ResponseHelper::success(
                'Module Updated Successfully',
                new ModuleResource($module->fresh()),
                'module'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed To Update Module. Error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Module $module): JsonResponse
    {
        try {
            $module->delete();

            return ResponseHelper::success(
                'Module Deleted Successfully.',
                null,
                'data',
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed To Delete Module. Error: ' . $e->getMessage(), 500);
        }
    }
}
