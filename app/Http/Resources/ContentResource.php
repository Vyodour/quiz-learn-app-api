<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $isAccessible = true;
        $isCompleted = false;

        if ($user instanceof User) {
            $isAccessible = $this->isPreviousContentCompleted($user);
            $isCompleted = $this->isCompletedByUser($user);
        }
        else {
            $isAccessible = $this->order_number === 1;
            $isCompleted = false;
        }

        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'order_number' => $this->order_number,
            'is_accessible' => $isAccessible,
            'is_completed' => $isCompleted,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
