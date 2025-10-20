<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ContentUnitOrder;
use App\Models\UserUnitProgress;

class UserDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'overall_completion_percentage' => $this->resource['overall_completion_percentage'],
            'completed_units_count' => $this->resource['completed_units_count'],
            'total_units_count' => $this->resource['total_units_count'],

            'next_unit_to_complete' => $this->when($this->resource['next_unit'], function () {
                return new ContentUnitOrderResource($this->resource['next_unit']);
            }),

            'code_challenge_pass_rate' => $this->resource['code_challenge_pass_rate'],
            'average_quiz_score' => $this->resource['average_quiz_score'],

            'module_progress' => $this->resource['module_progress'],
        ];
    }
}
