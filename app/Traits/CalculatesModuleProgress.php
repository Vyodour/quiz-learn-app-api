<?php

namespace App\Traits;

use App\Models\UserModuleEnrollment;

trait CalculatesModuleProgress
{
    public function calculateProgress(UserModuleEnrollment $enrollment): void
    {
        $enrollment->loadMissing(['user.unitProgress', 'module.contentUnitOrders']);

        $module = $enrollment->module;

        $totalUnits = $module->contentUnitOrders->count();

        if ($totalUnits === 0) {
            $enrollment->progress_percentage = 100;
            $enrollment->save();
            return;
        }

        $completedUnitsCount = $enrollment->user->unitProgress
            ->whereIn('content_unit_order_id', $module->contentUnitOrders->pluck('id'))
            ->where('is_completed', true)
            ->count();

        $progress = ($completedUnitsCount / $totalUnits) * 100;
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
