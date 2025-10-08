<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentUnitOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $this->whenLoaded('orderedUnit', function () {
            return $this->orderedUnit;
        });

        $type = strtolower(class_basename($this->ordered_unit_type));

        return [
            'id' => $this->id,
            'content_id' => $this->content_id,
            'type' => $type,
            'order_number' => $this->order_number,
            'is_completed' => $this->is_completed,

            'detail' => $this->when($detail !== null, $detail),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
