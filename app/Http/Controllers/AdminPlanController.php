<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Exception;

class AdminPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        try {
            $plans = Plan::all();
            return ResponseHelper::success('All plans retrieved successfully.', $plans, 'plans');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve plans. Error: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plans,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'duration_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $plan = Plan::create($validated);
            return ResponseHelper::success('Plan created successfully.', $plan, 'plan', 201);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create plan. Error: ' . $e->getMessage(), 500);
        }
    }

    public function show(Plan $plan): JsonResponse
    {
        return ResponseHelper::success('Plan retrieved successfully.', $plan, 'plan');
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:plans,name,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'billing_cycle' => 'sometimes|required|in:monthly,quarterly,yearly',
            'duration_days' => 'sometimes|required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $plan->update($validated);
            return ResponseHelper::success('Plan updated successfully.', $plan, 'plan');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update plan. Error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Plan $plan): JsonResponse
    {
        try {
            $planName = $plan->name;
            $plan->delete();
            return ResponseHelper::success("Plan '{$planName}' deleted successfully.", null);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete plan. Ensure no active subscriptions are linked. Error: ' . $e->getMessage(), 500);
        }
    }
}
