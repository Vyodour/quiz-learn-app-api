<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CodeChallengeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Digunakan untuk detail Code Challenge (tanpa test cases, untuk ringkasan).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'language' => $this->language,
            'passing_score' => $this->passing_score,
            'instructions' => $this->instructions,
        ];
    }
}
