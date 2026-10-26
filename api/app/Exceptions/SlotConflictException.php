<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a booking slot is already taken.
 * Translated to HTTP 409 Conflict in the exception handler.
 */
class SlotConflictException extends Exception
{
    public function __construct()
    {
        parent::__construct('Slot no longer available. Please choose a different time.');
    }
}
