<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthWorkerResource extends JsonResource
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
            'gender'   => $this->gender,
            'phone' => $this->phone,
            'working_hours' => $this->working_hours,
            'practitioner' => $this->practitioner,
            'date_of_birth' => $this->date_of_birth,
            'country' => $this->country,
            'region' => $this->region,
            'address' => $this->address,
            'religion' => $this->religion,
            'about_me' => $this->about,
            'image_url' => $this->image,
            'last_logged_in' => $this->last_logged_in,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
