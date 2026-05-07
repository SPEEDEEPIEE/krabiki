<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'workshop_id',
        'participant_user_id',
        'status',
        'paid',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'paid' => 'boolean',
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_user_id');
    }

    public function isCancelled(): bool
    {
        return $this->status === RegistrationStatus::Cancelled;
    }
}