<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hospital_id',
        'user_id',
        'sos_request_id',
        'request_type',
        'status',
        'notes',
        'user_notes',
    ];

    /**
     * Get the hospital that owns the request.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Get the user that made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the SOS request associated with this hospital request.
     */
    public function sosRequest(): BelongsTo
    {
        return $this->belongsTo(SosRequest::class);
    }
}


