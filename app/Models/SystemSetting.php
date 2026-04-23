<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    /** Get a setting value by key with optional default */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        if (!$setting) return $default;
        return static::cast($setting->value, $setting->type);
    }

    /** Get all settings grouped */
    public static function allGrouped(): array
    {
        return static::all()
            ->groupBy('group')
            ->toArray();
    }

    /** Set a value (upsert) */
    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => (string) $value]);
        Cache::forget("setting:{$key}");
    }

    /** Cast value to its declared type */
    private static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool)(int) $value,
            'integer' => (int)    $value,
            'json'    => json_decode($value, true),
            default   => (string) $value,
        };
    }
}
