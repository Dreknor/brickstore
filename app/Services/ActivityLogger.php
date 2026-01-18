<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity
     */
    public static function log(
        string $event,
        ?string $description = null,
        string $level = 'info',
        ?Model $model = null,
        ?array $properties = null
    ): ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'store_id' => $user?->store?->id,
            'log_level' => $level,
            'event' => $event,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }

    /**
     * Log debug event
     */
    public static function debug(string $event, ?string $description = null, ?Model $model = null, ?array $properties = null): ActivityLog
    {
        return self::log($event, $description, 'debug', $model, $properties);
    }

    /**
     * Log info event
     */
    public static function info(string $event, ?string $description = null, ?Model $model = null, ?array $properties = null): ActivityLog
    {
        return self::log($event, $description, 'info', $model, $properties);
    }

    /**
     * Log warning event
     */
    public static function warning(string $event, ?string $description = null, ?Model $model = null, ?array $properties = null): ActivityLog
    {
        return self::log($event, $description, 'warning', $model, $properties);
    }

    /**
     * Log error event
     */
    public static function error(string $event, ?string $description = null, ?Model $model = null, ?array $properties = null): ActivityLog
    {
        return self::log($event, $description, 'error', $model, $properties);
    }

    /**
     * Log critical event
     */
    public static function critical(string $event, ?string $description = null, ?Model $model = null, ?array $properties = null): ActivityLog
    {
        return self::log($event, $description, 'critical', $model, $properties);
    }
}

