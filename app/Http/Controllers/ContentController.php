<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Content;
use App\Http\Requests\StoreParentContentRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Http\Resources\ContentShowResource;
use App\Http\Resources\ContentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\ResponseHelper;
use Exception;

class ContentController extends Controller
{
    public function show(Content $content): JsonResponse
    {
        try {
            $content->load([
                'module',
                'orderedUnits.orderedUnit'
            ]);

            return ResponseHelper::success(
                'Content Detail Fetched.',
                new ContentShowResource($content),
                'content'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch content. Error: ' . $e->getMessage(), 500);
        }
    }

    public function store(StoreParentContentRequest $request, Module $module): JsonResponse // Menggunakan StoreParentContentRequest
    {
        try {
            $result = DB::transaction(function () use ($request, $module) {

                $nextOrderNumber = Content::where('module_id', $module->id)->max('order_number') + 1;

                $content = Content::create([
                    'module_id' => $module->id,
                    'title' => $request->title,
                    'slug' => Str::slug($request->title),
                    'order_number' => $nextOrderNumber,
                ]);

                return $content;
            });

            return ResponseHelper::success(
                'Content Berhasil Ditambahkan.',
                new ContentResource($result),
                'content',
                201
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Gagal membuat Content. Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateContentRequest $request, Content $content): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($request, $content) {

                $data = $request->validated();

                if ($request->has('title') && $request->title !== $content->title) {
                    $data['slug'] = Str::slug($request->title);
                }

                if ($request->has('order_number')) {
                    $data['order_number'] = $request->order_number;
                }

                $content->update($data);

                return $content;
            });

            return ResponseHelper::success(
                'Content Berhasil Diperbarui.',
                new ContentResource($result),
                'content'
            );

        } catch (Exception $e) {
            return ResponseHelper::error('Gagal memperbarui Content. Error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Content $content): JsonResponse
    {
        try {
            if ($content->delete()) {
                return ResponseHelper::success('Content Berhasil Dihapus.', null);
            }
            return ResponseHelper::error('Penghapusan gagal.', 500);

        } catch (Exception $e) {
            return ResponseHelper::error('Gagal menghapus Content. Error: ' . $e->getMessage(), 500);
        }
    }
}
