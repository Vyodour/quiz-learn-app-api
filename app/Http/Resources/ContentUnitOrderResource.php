<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\UserUnitProgress;

class ContentUnitOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $isAccessible = true;
        $isCompleted = false;

        if ($user) {
            $isAccessible = $this->canBeAccessedByUser($user) && $this->isPreviousUnitCompleted($user);

            $progress = UserUnitProgress::where('user_id', $user->id)
                ->where('content_unit_order_id', $this->id)
                ->first();

            $isCompleted = (bool)($progress && $progress->is_completed);
        } else {
             $isAccessible = !$this->is_premium && $this->order_number === 1;
        }

        return [
            'id' => $this->id,
            'content_id' => $this->content_id,
            'order_number' => $this->order_number,
            'ordered_unit_type' => $this->ordered_unit_type,
            'ordered_unit_id' => $this->ordered_unit_id,

            'is_premium' => (bool) $this->is_premium,
            'is_accessible' => $isAccessible,
            'is_completed' => $isCompleted,

            $this->mergeWhen($this->resource->relationLoaded('orderedUnit'), [
                'unit_detail' => new GenericContentUnitResource($this->orderedUnit),
            ]),
        ];
    }
}
