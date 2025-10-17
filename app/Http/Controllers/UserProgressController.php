<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContentUnitOrder;
use App\Models\UserUnitProgress;
use App\Models\QuizInformation; // Asumsi model ini ada untuk Quiz
use App\Models\CodeChallenge;   // Asumsi model ini ada untuk Code Challenge
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

    /**
     * Menyelesaikan unit konten.
     * Hanya unit bertipe Lesson yang dapat diselesaikan langsung melalui endpoint ini.
     *
     * @param ContentUnitOrder $contentUnitOrder Nama variabel disinkronkan dengan Route Parameter {contentUnitOrder}.
     */
    public function completeUnit(ContentUnitOrder $contentUnitOrder): JsonResponse
    {
        $user = Auth::user();

        try {
            // 1. Cek Akses & Urutan
            if (!$contentUnitOrder->canBeAccessedByUser($user) || !$contentUnitOrder->isPreviousUnitCompleted($user)) {
                 return ResponseHelper::error('Unit tidak dapat diakses (Premium atau Unit sebelumnya belum selesai)!', 403);
            }

            $unitType = $contentUnitOrder->ordered_unit_type;

            // 2. Cek Tipe Unit: Hanya izinkan Lesson diselesaikan langsung
            if ($unitType === QuizInformation::class) {
                // Untuk Kuis, harus melalui endpoint Quiz submission
                return ResponseHelper::error('Unit ini adalah Kuis. Silakan kirim jawaban Anda melalui endpoint submission kuis.', 400);
            }
            if ($unitType === CodeChallenge::class) {
                // Untuk Challenge, harus melalui endpoint Code Challenge submission
                return ResponseHelper::error('Unit ini adalah Code Challenge. Silakan kirim kode Anda melalui endpoint submission challenge.', 400);
            }

            // 3. Logika Penyelesaian (Berlaku untuk Lesson/Tipe Lain yang Tidak Perlu Submission)
            DB::transaction(function () use ($user, $contentUnitOrder) {
                UserUnitProgress::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'content_unit_order_id' => $contentUnitOrder->id, // Menggunakan $contentUnitOrder->id
                    ],
                    [
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]
                );
            });

            return ResponseHelper::success('Unit berhasil diselesaikan!', [
                'unit_id' => $contentUnitOrder->id,
                'user_id' => $user->id,
                'is_completed' => true,
            ], 'progress');

        } catch (Exception $e) {
            return ResponseHelper::error('Gagal menyelesaikan unit. Error: ' . $e->getMessage(), 500);
        }
    }

    public function resetUnit(ContentUnitOrder $contentUnitOrder): JsonResponse // Nama variabel disinkronkan
    {
        $user = Auth::user();

        try {
            $deleted = UserUnitProgress::where('user_id', $user->id)
                ->where('content_unit_order_id', $contentUnitOrder->id)
                ->delete();

            if ($deleted) {
                 return ResponseHelper::success('Unit progress has been reset!', [
                    'unit_id' => $contentUnitOrder->id,
                    'user_id' => $user->id,
                    'is_completed' => false,
                 ], 'progress');
            }

            return ResponseHelper::error('Unit progress not found!', 404);

        } catch (Exception $e) {
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

            $deletedCount = DB::transaction(function () use ($targetUserId, $unitIds) {
                 return UserUnitProgress::where('user_id', $targetUserId)
                     ->whereIn('content_unit_order_id', $unitIds)
                     ->delete();
            });

            return ResponseHelper::success(
                "{$deletedCount} progress records for Content ID: {$contentId} has been reset for User ID: {$targetUserId}",
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
