<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenericContentUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = strtolower(class_basename($this->resource));

        return [
            'type' => $type,
            'attributes' => $this->resource->toArray(),
        ];
    }
}
