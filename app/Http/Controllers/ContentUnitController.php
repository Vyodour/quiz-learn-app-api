<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Content;
use App\Models\ContentUnitOrder;
use App\Models\Lesson;
use App\Models\QuizInformation;
use App\Models\CodeChallenge;
use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\UpdateContentUnitRequest;
use App\Http\Resources\ContentUnitOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Exception;

class ContentUnitController extends Controller
{
    protected $contentableMap = [
        'lesson' => Lesson::class,
        'quiz' => QuizInformation::class,
        'challenge' => CodeChallenge::class,
    ];

    public function index(Content $content): JsonResponse
    {
        try {
            $orderedUnits = $content->orderedUnits()
                                     ->with('orderedUnit')
                                     ->orderBy('order_number')
                                     ->get();

            return ResponseHelper::success(
                'Content Units Fetched Successfully',
                ContentUnitOrderResource::collection($orderedUnits),
                'ordered_units'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to Fetch Content Units. Error: ' . $e->getMessage(), 500);
        }
    }

    public function show(Content $content, ContentUnitOrder $orderedUnit): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
             return ResponseHelper::error('Unauthorized. Please log in.', 401);
        }

        if ($orderedUnit->content_id !== $content->id) {
            return ResponseHelper::error('Content unit does not belong to the specified content.', 404);
        }

        if (!$orderedUnit->canBeAccessedByUser($user)) {
             return ResponseHelper::error('This is premium content and need subscription.', 403);
        }

        if (!$orderedUnit->isPreviousUnitCompleted($user)) {
            return ResponseHelper::error('Finish the previous content to access this.', 403);
        }

        $orderedUnit->load('orderedUnit');

        try {
            return ResponseHelper::success(
                'Content Unit Detail Fetched Successfully',
                new ContentUnitOrderResource($orderedUnit),
                'content_unit_detail'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to Fetch Content Unit Detail. Error: ' . $e->getMessage(), 500);
        }
    }

    public function store(StoreContentRequest $request, Content $content): JsonResponse
    {
        $type = $request->type;
        $specificData = $request->order_data;

        if (!isset($this->contentableMap[$type])) {
             return ResponseHelper::error('Content type unvalid!.', 422);
        }

        $ModelClass = $this->contentableMap[$type];

        try {
            $result = DB::transaction(function () use ($content, $ModelClass, $specificData, $request) {

                $nextOrderNumber = ContentUnitOrder::where('content_id', $content->id)->max('order_number') + 1;
                $contentable = $ModelClass::create($specificData);

                $contentUnitOrder = ContentUnitOrder::create([
                    'content_id' => $content->id,
                    'title' => $request->title,
                    'order_number' => $nextOrderNumber,
                    'is_completed' => false,
                    'ordered_unit_type' => $contentable::class,
                    'ordered_unit_id' => $contentable->id,
                    'is_premium' => $request->boolean('is_premium', false),
                ]);

                $contentUnitOrder->load('orderedUnit');

                return $contentUnitOrder;
            });

            return ResponseHelper::success(
                'Content unit created.',
                new ContentUnitOrderResource($result),
                'content_unit',
                201
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create content unit. Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateContentUnitRequest $request, ContentUnitOrder $orderedUnit): JsonResponse
    {
        try {
            $contentable = $orderedUnit->orderedUnit;

            if (!$contentable) {
                return ResponseHelper::error('Content unit not found!.', 404);
            }

            $result = DB::transaction(function () use ($request, $orderedUnit, $contentable) {

                $updateOrderData = [];

                if ($request->has('title')) {
                    $updateOrderData['title'] = $request->title;
                }

                if ($request->has('order_number')) {
                    $orderedUnit->update(['order_number' => $request->order_number]);
                }

                if ($request->has('is_premium')) {
                    $orderedUnit->update(['is_premium' => $request->boolean('is_premium')]);
                }

                if ($request->has('order_data') && is_array($request->order_data)) {
                    $contentable->update($request->order_data);
                }

                $orderedUnit->load('orderedUnit');
                return $orderedUnit;
            });

            return ResponseHelper::success(
                'Content unit has been updated.',
                new ContentUnitOrderResource($result),
                'content_unit'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update content unit. Error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(ContentUnitOrder $orderedUnit): JsonResponse
    {
        try {
            $contentable = $orderedUnit->orderedUnit;

            $success = DB::transaction(function () use ($orderedUnit, $contentable) {

                if ($contentable) {
                    $contentable->delete();
                }

                $orderedUnit->delete();

                return true;
            });

            if ($success) {
                return ResponseHelper::success('Content unit deleted.', null);
            }
            return ResponseHelper::error('Failed to delete content unit.', 500);

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete content unit. Error: ' . $e->getMessage(), 500);
        }
    }
}
