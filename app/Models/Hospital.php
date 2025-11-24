<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'location',
        'latitude',
        'longitude',
        'phone',
    ];

    /**
     * Get the user that owns the hospital.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all requests for this hospital.
     */
    public function hospitalRequests(): HasMany
    {
        return $this->hasMany(HospitalRequest::class);
    }

    /**
     * Find the nearest hospital to given coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @param float|null $maxDistanceKm Maximum distance in kilometers (optional)
     * @return Hospital|null
     */
    public static function findNearest(float $latitude, float $longitude, ?float $maxDistanceKm = null): ?Hospital
    {
        $query = static::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                hospitals.*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->orderBy('distance');

        if ($maxDistanceKm !== null) {
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?
            ", [$latitude, $longitude, $latitude, $maxDistanceKm]);
        }

        return $query->first();
    }

    /**
     * Find nearest hospitals (multiple) to given coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @param float|null $maxDistanceKm Maximum distance in kilometers (optional)
     * @param int $limit Maximum number of hospitals to return
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findNearestMultiple(float $latitude, float $longitude, ?float $maxDistanceKm = null, int $limit = 5)
    {
        $query = static::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                hospitals.*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->orderBy('distance')
            ->limit($limit);

        if ($maxDistanceKm !== null) {
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?
            ", [$latitude, $longitude, $latitude, $maxDistanceKm]);
        }

        return $query->get();
    }
}
