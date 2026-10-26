<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHours extends Model
{
    protected $fillable = [
        'staff_id',
        'weekday',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Whether this working-hours row effectively ends at midnight (24:00).
     * We store midnight-end as '00:00:00' to fit the TIME column type.
     * When end_time <= start_time, treat end_time as the next day's 00:00.
     */
    public function endsAtMidnight(): bool
    {
        return $this->end_time === '00:00:00' || $this->end_time <= $this->start_time;
    }
}
