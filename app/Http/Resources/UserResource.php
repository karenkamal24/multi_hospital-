<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'user_type' => $this->user_type,
            'blood' => $this->blood,
            'image' => $this->image,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'fcm_token' => $this->when($request->user()?->id === $this->id, $this->fcm_token), // فقط للمستخدم نفسه
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
