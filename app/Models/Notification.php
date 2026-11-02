<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'module',
        'source',
        'severity',
        'title',
        'subtitle',
        'message',
        'action_label',
        'action_link',
        'dedupe_key',
        'is_read',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Notifications belonging to a user that have not been cleared. */
    public function scopeVisibleTo($query, int $userId)
    {
        return $query->where('user_id', $userId)->whereNull('dismissed_at');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
