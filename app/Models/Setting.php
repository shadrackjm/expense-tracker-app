<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class Setting extends Model
{
    use HasFactory; // Keep HasFactory if you plan to use model factories

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
    ];

    /**
     * Get the user that owns the setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a setting value for the authenticated user.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (!Auth::check()) {
            return $default; // Return default if no user is authenticated
        }

        return static::where('user_id', Auth::id())
                     ->where('key', $key)
                     ->value('value') ?? $default;
    }

    /**
     * Set a setting value for the authenticated user.
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return static
     */
    public static function set($key, $value, $type = 'general')
    {
        if (!Auth::check()) {
            // Log an error or handle cases where an unauthenticated user tries to set a setting
            error_log("Attempt to set setting '{$key}' by unauthenticated user.");
            return null;
        }

        return static::updateOrCreate(
            ['user_id' => Auth::id(), 'key' => $key],
            ['value' => (string) $value, 'type' => $type] // Cast value to string for storage
        );
    }
}
