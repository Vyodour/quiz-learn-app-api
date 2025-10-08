<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'order_number' => $this->order_number,
            'module' => $this->whenLoaded('module', new ModuleResource($this->module)),
            'ordered_units' => ContentUnitOrderResource::collection($this->whenLoaded('orderedUnits')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
