<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ModuleSummaryResource;

class LearningPathShowResource extends JsonResource
{
    /**
     * Transform the resource into an array (for detail view/show).
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

            // Relasi Modules: Dimuat jika di-eager load
             'modules' => $this->whenLoaded('modules', function () {
                // Menggunakan ModuleSummaryResource yang ringan dan efisien
                return ModuleSummaryResource::collection($this->modules);
            }),
        ];
    }
}
