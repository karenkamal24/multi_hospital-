<?php

namespace App\Services;

use App\Models\User;

class LocationService
{
    /**
     * Update user location
     */
    public function updateLocation(User $user, float $latitude, float $longitude): User
    {
        $user->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return $user->fresh();
    }
}

