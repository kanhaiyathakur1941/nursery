<?php

namespace App\Http\Resources;

use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CheckIn
 */
class OccupantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'child_id' => $this->child_id,
            'name' => $this->child->name,
            'checked_in_at' => $this->checked_in_at,
        ];
    }
}
