<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'phone'    => $this->phone,
            'about'    => $this->about,
            'gender'   => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'image' => $this->image,
            'address' => $this->address,
            'religion' => $this->religion,
            'country' => $this->country,
            'region' => $this->region,
            'last_logged_in' => $this->last_logged_in,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'two_fa_enabled' => $this->two_factor_enabled,
            'joined' => $this->created_at,
        ];
    }
}
