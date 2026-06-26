<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember("setting_val_{$key}", 3600, function () use ($key, $default) {
            $row = static::where('key', $key)->value('value');
            return $row ?? $default;
        });

        return $value;
    }

    public static function set(string $key, mixed $value, string $group = 'general', ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'description' => $description]
        );
        Cache::forget("setting_val_{$key}");
    }

    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->pluck('value', 'key')->toArray();
        return $settings;
    }
}
