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

    public function update(UpdateContentUnitRequest $request, ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        try {
            $contentable = $contentUnitOrder->orderedUnit;

            if (!$contentable) {
                return ResponseHelper::error('Content unit not found!.', 404);
            }

            $result = DB::transaction(function () use ($request, $contentUnitOrder, $contentable) {

                if ($request->has('order_number')) {
                    $contentUnitOrder->update(['order_number' => $request->order_number]);
                }

                if ($request->has('is_premium')) {
                    $contentUnitOrder->update(['is_premium' => $request->boolean('is_premium')]);
                }

                if ($request->has('order_data') && is_array($request->order_data)) {
                    $contentable->update($request->order_data);
                }

                $contentUnitOrder->load('orderedUnit');
                return $contentUnitOrder;
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

    public function destroy(ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        try {
            $contentable = $contentUnitOrder->orderedUnit;

            $success = DB::transaction(function () use ($contentUnitOrder, $contentable) {

                if ($contentable) {
                    $contentable->delete();
                }

                $contentUnitOrder->delete();

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
