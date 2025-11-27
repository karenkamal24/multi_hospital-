<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SosRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'blood' => $this->blood,
            'patient' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'phone' => $this->user->phone,
                    'email' => $this->user->email,
                    'blood' => $this->user->blood,
                    'image' => $this->user->image ? asset('storage/' . $this->user->image) : null,
                ];
            }),
            'accepted_donor' => $this->whenLoaded('acceptedDonor', function () {
                return [
                    'id' => $this->acceptedDonor->id,
                    'name' => $this->acceptedDonor->name,
                    'phone' => $this->acceptedDonor->phone,
                    'email' => $this->acceptedDonor->email,
                    'blood' => $this->acceptedDonor->blood,
                    'image' => $this->acceptedDonor->image ? asset('storage/' . $this->acceptedDonor->image) : null,
                ];
            }),
            'hospital' => $this->whenLoaded('hospital', function () {
                return [
                    'id' => $this->hospital->id,
                    'name' => $this->hospital->name,
                    'address' => $this->hospital->address,
                    'latitude' => $this->hospital->latitude,
                    'longitude' => $this->hospital->longitude,
                    'phone' => $this->hospital->phone,
                ];
            }),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'status' => $this->status,
            'operation_status' => $this->operation_status,
            'accepted_donors_count' => $this->when(isset($this->accepted_donors_count), $this->accepted_donors_count),
            'distance' => $this->when(isset($this->distance), round($this->distance, 2)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

