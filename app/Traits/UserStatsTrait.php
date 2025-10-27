<?php

namespace App\Traits;

use App\Models\ContentUnitOrder;
use App\Models\Module;
use App\Models\UserCodeSubmission;
use App\Models\UserModuleEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

trait UserStatsTrait
{
    protected function calculateUserStats($user): array
    {
        $totalUnits = ContentUnitOrder::count();
        $completedUnitsCount = $user->userProgresses()->where('is_completed', true)->count();
        $overallCompletionPercentage = ($totalUnits > 0) ? round(($completedUnitsCount / $totalUnits) * 100) : 0;

        $nextUnit = ContentUnitOrder::orderBy('order_number')
            ->whereDoesntHave('userProgresses', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_completed', true);
            })
            ->with('orderedUnit.content.module')
            ->first();

        $totalSubmissions = UserCodeSubmission::where('user_id', $user->id)->count();
        $passedSubmissions = UserCodeSubmission::where('user_id', $user->id)->where('is_passed', true)->count();
        $challengePassRate = ($totalSubmissions > 0) ? round(($passedSubmissions / $totalSubmissions) * 100) : 0;

        $averageQuizScore = DB::table('user_quiz_attempts')
            ->where('user_id', $user->id)
            ->avg('score') ?? 0;
        $averageQuizScore = round($averageQuizScore, 0);

        $enrolledModuleIds = UserModuleEnrollment::where('user_id', $user->id)->pluck('module_id');
        $moduleProgress = Module::whereIn('id', $enrolledModuleIds)
            ->with(['contents.orderedUnits.userProgresses' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get()
            ->map(function ($module) {
                $totalUnits = $module->contents->flatMap(fn ($content) => $content->orderedUnits)->count();
                $completedUnits = $module->contents->flatMap(fn ($content) => $content->orderedUnits)
                    ->filter(fn ($unit) => $unit->userProgresses->isNotEmpty() && $unit->userProgresses->first()->is_completed)
                    ->count();

                $completionPercentage = ($totalUnits > 0) ? round(($completedUnits / $totalUnits) * 100) : 0;

                return [
                    'module_id' => $module->id,
                    'title' => $module->title,
                    'completion_percentage' => $completionPercentage,
                    'total_units' => $totalUnits,
                ];
            });

        $currentStreak = method_exists($user, 'calculateCurrentStreak') ? $user->calculateCurrentStreak() : 0;

        return [
            'overall_completion_percentage' => $overallCompletionPercentage,
            'completed_units_count' => $completedUnitsCount,
            'total_units_count' => $totalUnits,
            'next_unit' => $nextUnit,
            'code_challenge_pass_rate' => $challengePassRate,
            'average_quiz_score' => $averageQuizScore,
            'module_progress' => $moduleProgress,
            'current_streak_days' => $currentStreak,
        ];
    }
}
