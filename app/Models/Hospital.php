<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    /**
     * Get the user that owns the hospital.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
