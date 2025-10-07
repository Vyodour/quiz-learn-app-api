<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'learning_path_id' => $this->learning_path_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'duration_minutes' => $this->duration,
            'order_number' => $this->order_number,
            'level' => $this->level,
            'rating' => (float) $this->rating,

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
