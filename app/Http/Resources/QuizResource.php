<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Digunakan untuk detail Quiz (tanpa pertanyaan, untuk ringkasan).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'passing_score' => $this->passing_score,
            'time_limit_minutes' => $this->time_limit,

            // Relasi pertanyaan akan di-eager load jika diperlukan di endpoint Quiz detail
            // 'questions' => QuizQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
