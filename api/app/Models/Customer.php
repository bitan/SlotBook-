<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'notes',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Find a customer by phone or create one.
     * Phone is the identity key — customers don't need accounts.
     */
    public static function findOrCreateByPhone(
        string $phone,
        string $name,
        ?string $email = null,
    ): self {
        return static::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name, 'email' => $email],
        );
    }
}
