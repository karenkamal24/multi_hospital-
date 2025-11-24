<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SosRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'accepted_donor_id',
        'hospital_id',
        'type',
        'blood',
        'latitude',
        'longitude',
        'radius_km',
        'status',
        'operation_status',
        'description',
    ];

    /**
     * Get the user that owns the SOS request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all hospital requests associated with this SOS request.
     */
    public function hospitalRequests(): HasMany
    {
        return $this->hasMany(HospitalRequest::class);
    }

    /**
     * Get the accepted donor for this SOS request.
     */
    public function acceptedDonor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_donor_id');
    }

    /**
     * Get the hospital for this SOS request.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
