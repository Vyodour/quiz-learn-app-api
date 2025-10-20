<?php

namespace App\Traits;

use App\Models\UserModuleEnrollment;

trait CalculatesModuleProgress
{
    public function calculateProgress(UserModuleEnrollment $enrollment): void
    {
        $enrollment->loadMissing(['user.unitProgress', 'user.quizAttempts', 'module']);

        $module = $enrollment->module;

        $totalUnits = $module->contentUnitOrders->count();
        $totalQuizzes = $module->quizzesInformation->count();
        $totalElements = $totalUnits + $totalQuizzes;

        if ($totalElements === 0) {
            $enrollment->progress_percentage = 0;
            $enrollment->save();
            return;
        }

        $completedUnitsCount = $enrollment->user->unitProgress
            ->whereIn('content_unit_order_id', $module->contentUnitOrders->pluck('id'))
            ->where('is_completed', true)
            ->count();

        $passedQuizzesCount = $enrollment->user->quizAttempts
            ->whereIn('quiz_information_id', $module->quizzesInformation->pluck('id'))
            ->where('is_passed', true)
            ->unique('quiz_information_id')
            ->count();


        $completedElements = $completedUnitsCount + $passedQuizzesCount;

        $progress = ($completedElements / $totalElements) * 100;
        $progress = min(100, round($progress));

        $enrollment->progress_percentage = $progress;

        if ($progress >= 100 && is_null($enrollment->completion_date)) {
            $enrollment->completion_date = now();
        } elseif ($progress < 100 && !is_null($enrollment->completion_date)) {
            $enrollment->completion_date = null;
        }

        $enrollment->save();
    }
}
