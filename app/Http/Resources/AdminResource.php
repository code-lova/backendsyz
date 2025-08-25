<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'fullname' => $this->name,
            'email'    => $this->email,
            'image' => $this->image,
            'last_logged_in' => $this->last_logged_in,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'two_fa_enabled' => $this->two_factor_enabled,
            'joined' => $this->created_at,
        ];
    }
}
