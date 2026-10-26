<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * All valid keys for the settings table.
 *
 * This is the single source of truth. Never use raw strings
 * for settings keys anywhere in the codebase — always reference
 * a value from this enum.
 */
enum SettingKey: string
{
    case BusinessName         = 'business_name';
    case BusinessPhone        = 'business_phone';
    case BusinessEmail        = 'business_email';
    case BusinessAddress      = 'business_address';
    case BusinessTimezone     = 'business_timezone';
    case AutoConfirm          = 'auto_confirm';
    case CancelWindowHours    = 'cancel_window_hours';
    case MinNoticeMinutes     = 'min_notice_minutes';
    case TelegramOwnerChatId  = 'telegram_owner_chat_id';
    case GoogleMapsUrl        = 'google_maps_url';

    /** Human-readable label for admin UI display. */
    public function label(): string
    {
        return match ($this) {
            self::BusinessName        => 'Business Name',
            self::BusinessPhone       => 'Business Phone',
            self::BusinessEmail       => 'Business Email',
            self::BusinessAddress     => 'Business Address',
            self::BusinessTimezone    => 'Timezone',
            self::AutoConfirm         => 'Auto-confirm new bookings',
            self::CancelWindowHours   => 'Cancel/reschedule window (hours before)',
            self::MinNoticeMinutes    => 'Minimum booking notice (minutes)',
            self::TelegramOwnerChatId => 'Telegram chat ID for notifications',
            self::GoogleMapsUrl       => 'Google Maps embed URL',
        };
    }

    /** The expected value type, used for casting when reading. */
    public function type(): string
    {
        return match ($this) {
            self::AutoConfirm       => 'boolean',
            self::CancelWindowHours,
            self::MinNoticeMinutes  => 'integer',
            default                 => 'string',
        };
    }
}
