<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningPathResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Digunakan untuk menampilkan list atau ringkasan data Learning Path.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'is_published' => (bool) $this->is_published,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

        ];
    }
}
