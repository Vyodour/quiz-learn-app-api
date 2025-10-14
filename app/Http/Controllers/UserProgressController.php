<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContentUnitOrder;
use App\Models\UserUnitProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use Exception;

class UserProgressController extends Controller
{
    public function __construct ()
    {
        $this->middleware('auth:sanctum');
    }

    public function completeUnit(ContentUnitOrder $unitOrder): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return ResponseHelper::error('User Unauthenticated!', 401);
        }
        try {
            DB::transaction(function () use ($user, $unitOrder) {
                UserUnitProgress::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'content_unit_order_id' => $unitOrder->id,
                    ],
                    [
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]
                );
            });

            return ResponseHelper::success('Content completed!', [
                'unit_id' => $unitOrder->id,
                'user_id' => $user->id,
                'is_completed' => true,
            ], 'progress');

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to complete content. Error: ' . $e->getMessage(), 500);
        }
    }
    public function resetUnit(ContentUnitOrder $unitOrder): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return ResponseHelper::error('User Unauthenticated!', 401);
        }

        try {
            $deleted = UserUnitProgress::where('user_id', $user->id)
                ->where('content_unit_order_id', $unitOrder->id)
                ->delete();

            if ($deleted) {
                 return ResponseHelper::success('Content progress has been reset!', [
                    'unit_id' => $unitOrder->id,
                    'user_id' => $user->id,
                    'is_completed' => false,
                ], 'progress');
            }

            return ResponseHelper::error('Content progress not found!', 404);

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to reset content progress!. Error: ' . $e->getMessage(), 500);
        }
    }
}
