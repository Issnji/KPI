<?php // app/Http/Resources/UserResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'is_active'  => $this->is_active,
            'role'       => $this->whenLoaded('role', fn () => $this->role?->only(['id', 'name'])),
            'position'   => $this->whenLoaded('position', fn () => $this->position?->only(['id', 'name'])),
            'created_at' => $this->created_at,
        ];
    }
}