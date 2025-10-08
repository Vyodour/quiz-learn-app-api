<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'passing_score' => $this->passing_score,
            'time_limit_minutes' => $this->time_limit,

            'questions' => QuizQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
