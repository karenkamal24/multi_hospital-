<?php

namespace App\Services;

use App\Models\Hospital;

class HospitalService
{
    /**
     * Find nearest hospital to given coordinates
     */
    public function findNearest(float $latitude, float $longitude, ?float $radius = null): ?array
    {
        $hospital = Hospital::findNearest($latitude, $longitude, $radius);

        if (!$hospital) {
            return null;
        }

        // Calculate distance
        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            $hospital->latitude,
            $hospital->longitude
        );

        return [
            'id' => $hospital->id,
            'name' => $hospital->name,
            'address' => $hospital->address,
            'latitude' => $hospital->latitude,
            'longitude' => $hospital->longitude,
            'phone' => $hospital->phone,
            'distance' => round($distance, 2),
            'available' => true,
        ];
    }

    /**
     * Calculate distance between two coordinates in kilometers
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

