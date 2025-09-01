<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ErrorLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'errorlogs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'error_type',
        'error_code',
        'error_message',
        'error_details',
        'api_endpoint',
        'http_method',
        'request_payload',
        'response_payload',
        'user_agent',
        'ip_address',
        'user_id',
        'session_id',
        'stack_trace',
        'environment',
        'is_resolved',
        'resolution_notes',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'error_details' => 'json',
        'request_payload' => 'json',
        'response_payload' => 'json',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that triggered this error.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unresolved errors.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope a query to only include resolved errors.
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope a query to filter by error type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('error_type', $type);
    }

    /**
     * Scope a query to filter by API endpoint.
     */
    public function scopeByEndpoint($query, $endpoint)
    {
        return $query->where('api_endpoint', $endpoint);
    }

    /**
     * Scope a query to filter by environment.
     */
    public function scopeByEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Mark this error as resolved.
     */
    public function markAsResolved($notes = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => Carbon::now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Create a new error log entry.
     */
    public static function logError(array $data)
    {
        return static::create(array_merge([
            'environment' => app()->environment(),
            'created_at' => Carbon::now(),
        ], $data));
    }
}
