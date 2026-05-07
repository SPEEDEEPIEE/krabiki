<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_name',
        'contacts',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }
}