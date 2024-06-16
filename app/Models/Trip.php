<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'station_trip', 'trip_id', 'station_id')
            ->withPivot('order', 'arrived_seats', 'departed_seats')
            ->withTimestamps();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
