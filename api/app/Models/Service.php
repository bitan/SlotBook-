<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'duration_min',
        'buffer_min',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_min' => 'integer',
            'buffer_min'   => 'integer',
            'price'        => 'integer', // stored in cents (ETB)
            'is_active'    => 'boolean',
        ];
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'service_staff');
    }

    /** Price formatted for display (e.g. "150.00 ETB"). */
    public function formattedPrice(): string
    {
        return number_format($this->price / 100, 2) . ' ETB';
    }

    /** Total time this service blocks in a schedule (duration + buffer). */
    public function totalBlockMinutes(): int
    {
        return $this->duration_min + $this->buffer_min;
    }
}
