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
            'id'       => $this->id,
            'fullname' => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'gender'   => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'image' => $this->image,
            'place_of_birth' => $this->place_of_birth,
            'blood_group' => $this->blood_group,
            'genotype' => $this->genotype,
            'address' => $this->address,
            'religion' => $this->religion,
            'nationality' => $this->nationality,
            'date_of_birth' => $this->date_of_birth,
            'weight'   => $this->weight,
            'height'   => $this->height,
        ];
    }
}
