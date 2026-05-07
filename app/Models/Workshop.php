<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'venue_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'max_participants',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function approvedRegistrations()
    {
        return $this->registrations()->where('status', 'approved');
    }

    public function availableSpots(): int
    {
        $limit = min($this->venue->capacity, $this->max_participants);
        $approved = $this->approvedRegistrations()->count();
        return max(0, $limit - $approved);
    }

    public function isFull(): bool
    {
        return $this->availableSpots() <= 0;
    }
}