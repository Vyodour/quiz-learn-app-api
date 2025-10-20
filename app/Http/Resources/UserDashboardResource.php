<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ContentUnitOrder;
use App\Models\UserUnitProgress;

class UserDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'overallCompletion' => $this->resource['overall_completion_percentage'],
            'completedUnits' => $this->resource['completed_units_count'],
            'totalUnits' => $this->resource['total_units_count'],
            'nextUnit' => $this->resource['next_unit'] ? [
                'id' => $this->resource['next_unit']->id,
                'title' => $this->resource['next_unit']->orderedUnit->title ?? 'N/A',
                'order_number' => $this->resource['next_unit']->order_number,
            ] : null,
            'challengePassRate' => $this->resource['code_challenge_pass_rate'],
            'averageQuizScore' => $this->resource['average_quiz_score'],
            'moduleProgress' => $this->resource['module_progress'],
        ];
    }
}
