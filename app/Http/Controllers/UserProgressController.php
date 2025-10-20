<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContentUnitOrder;
use App\Models\UserUnitProgress;
use App\Models\UserModuleEnrollment;
use App\Models\QuizInformation;
use App\Models\CodeChallenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Exception;

class UserProgressController extends Controller
{
    public function __construct ()
    {
        $this->middleware('auth:sanctum');
    }

    public function completeUnit(ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();
        $progressPercentage = 0;

        try {
            if (!$contentUnitOrder->canBeAccessedByUser($user) || !$contentUnitOrder->isPreviousUnitCompleted($user)) {
                 return ResponseHelper::error('Unit cannot be accessed!', 403);
            }

            $unitType = $contentUnitOrder->ordered_unit_type;

            if ($unitType === QuizInformation::class) {
                return ResponseHelper::error('This is a quiz. Please send your answer.', 400);
            }
            if ($unitType === CodeChallenge::class) {
                return ResponseHelper::error('This is a code submission. Please send your submission.', 400);
            }

            DB::transaction(function () use ($user, $contentUnitOrder, &$progressPercentage) {
                UserUnitProgress::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'content_unit_order_id' => $contentUnitOrder->id,
                    ],
                    [
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]
                );

                $module = $contentUnitOrder->content?->module;

                if(!$module) {
                    throw new \Exception('Module data can not be retrieved.');
                }

                $enrollment = UserModuleEnrollment::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->first();

                if (!$enrollment) {
                    throw new \Exception('User is not enrolled in this module.');
                }

                $totalUnits = $module->contentUnitOrders()->count();

                if ($totalUnits === 0) {
                    $progressPercentage = 100;
                } else {
                    $completedUnits = UserUnitProgress::where('user_id', $user->id)
                        ->where('is_completed', true)
                        ->whereHas('unitOrder', function ($query) use ($module) {
                             $query->whereHas('content', function ($q) use ($module) {
                                $q->where('module_id', $module->id);
                             });
                        })
                        ->count();

                    $progressPercentage = min(100, round(($completedUnits / $totalUnits) * 100));
                }

                $enrollment->progress_percentage = $progressPercentage;

                if ($progressPercentage === 100 && is_null($enrollment->end_date)) {
                    $enrollment->end_date = now();
                }

                $enrollment->save();
            });

            return ResponseHelper::success('Unit has been finished!', [
                'unit_id' => $contentUnitOrder->id,
                'user_id' => $user->id,
                'is_completed' => true,
                'module_progress_percentage' => $progressPercentage,
            ], 'progress_updated');

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to finish the unit. Error: ' . $e->getMessage(), 500);
        }
    }

    public function resetUnit(ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();
        $progressPercentage = 0;

        try {
            DB::transaction(function () use ($user, $contentUnitOrder, &$progressPercentage) {
                $deleted = UserUnitProgress::where('user_id', $user->id)
                    ->where('content_unit_order_id', $contentUnitOrder->id)
                    ->delete();

                if ($deleted) {
                    $module = $contentUnitOrder->content?->module;

                    if (!$module) {
                        throw new \Exception('Module data could not be retrieved for reset.');
                    }

                    $enrollment = UserModuleEnrollment::where('user_id', $user->id)
                        ->where('module_id', $module->id)
                        ->first();

                    if (!$enrollment) {
                        return;
                    }

                    $totalUnits = $module->contentUnitOrders()->count();

                    if ($totalUnits === 0) {
                        $progressPercentage = 100;
                    } else {
                        $completedUnits = UserUnitProgress::where('user_id', $user->id)
                            ->where('is_completed', true)
                            ->whereHas('unitOrder', function ($query) use ($module) {
                                 $query->whereHas('content', function ($q) use ($module) {
                                    $q->where('module_id', $module->id);
                                 });
                            })
                            ->count();

                        $progressPercentage = min(100, round(($completedUnits / $totalUnits) * 100));
                    }

                    $enrollment->progress_percentage = $progressPercentage;
                    if ($enrollment->end_date && $progressPercentage < 100) {
                        $enrollment->end_date = null;
                    }

                    $enrollment->save();
                } else {
                    throw new \Exception('Unit progress not found for reset.', 404);
                }
            });

            return ResponseHelper::success('Unit progress has been reset!', [
                'unit_id' => $contentUnitOrder->id,
                'user_id' => $user->id,
                'is_completed' => false,
                'module_progress_percentage' => $progressPercentage,
            ], 'progress_reset');

        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                 return ResponseHelper::error('Unit progress not found!', 404);
            }
            return ResponseHelper::error('Failed to reset unit progress. Error: ' . $e->getMessage(), 500);
        }
    }

    public function resetAllContentProgress(Request $request): JsonResponse
    {
        $request->validate([
            'content_id' => ['required', 'exists:contents,id'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $targetUserId = $request->input('user_id') ?? Auth::id();
        $contentId = $request->input('content_id');

        if (!$targetUserId) {
             return ResponseHelper::error('Target User ID is required!', 400);
        }

        try {
            $unitIds = ContentUnitOrder::where('content_id', $contentId)->pluck('id');

            if ($unitIds->isEmpty()) {
                return ResponseHelper::error('Content not found or has no units.', 404);
            }

            $content = \App\Models\Content::find($contentId);
            $moduleId = $content?->module_id;


            $deletedCount = DB::transaction(function () use ($targetUserId, $unitIds, $moduleId) {
                 $count = UserUnitProgress::where('user_id', $targetUserId)
                     ->whereIn('content_unit_order_id', $unitIds)
                     ->delete();

                if ($moduleId) {
                    UserModuleEnrollment::where('user_id', $targetUserId)
                        ->where('module_id', $moduleId)
                        ->update([
                            'progress_percentage' => 0,
                            'end_date' => null,
                        ]);
                }

                return $count;
            });

            return ResponseHelper::success(
                "{$deletedCount} progress records for Content ID: {$contentId} has been reset for User ID: {$targetUserId}. Module progress reset.",
                [
                    'content_id' => $contentId,
                    'user_id' => $targetUserId,
                    'records_deleted' => $deletedCount,
                ],
                'progress_reset_bulk'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to reset content progress in bulk. Error: ' . $e->getMessage(), 500);
        }
    }
}
