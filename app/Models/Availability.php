<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    protected $fillable = [
        'resource_id',
        'service_id',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'is_available',
        'valid_from',
        'valid_until',
        'capacity',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'specific_date' => 'date',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_available' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}