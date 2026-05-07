<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'capacity',
        'hourly_fee',
    ];

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }
}