<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'key' => SettingKey::class,
        ];
    }

    // ──────────────────────────────────────────────
    // Typed accessors
    // ──────────────────────────────────────────────

    /**
     * Get a setting value, cast to the type declared in SettingKey.
     * Uses a 10-minute cache to avoid repeated DB reads on every request.
     */
    public static function get(SettingKey $key, mixed $default = null): mixed
    {
        $cacheKey = "setting:{$key->value}";

        $raw = Cache::remember($cacheKey, 600, function () use ($key) {
            return static::find($key->value)?->value;
        });

        if ($raw === null) {
            return $default;
        }

        return match ($key->type()) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            default   => $raw,
        };
    }

    /**
     * Set a setting value and clear the cache entry.
     */
    public static function set(SettingKey $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key->value],
            ['value' => (string) $value],
        );

        Cache::forget("setting:{$key->value}");
    }

    /**
     * Get all settings as a key => typed-value array.
     */
    public static function allTyped(): array
    {
        return collect(SettingKey::cases())
            ->mapWithKeys(fn (SettingKey $k) => [$k->value => static::get($k)])
            ->all();
    }
}
