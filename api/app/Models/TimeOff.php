<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOff extends Model
{
    protected $fillable = [
        'staff_id',
        'starts_at',
        'ends_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime', // UTC
            'ends_at'   => 'datetime', // UTC
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Returns true if this time-off applies to the whole business. */
    public function isBusinessWide(): bool
    {
        return $this->staff_id === null;
    }
}
