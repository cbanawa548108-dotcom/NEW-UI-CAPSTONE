<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'archived_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'archived_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user is a cashier.
     */
    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    /**
     * Check if user has access to a specific feature.
     */
    public function canAccess(string $feature): bool
    {
        $permissions = [
            'owner' => [
                'dashboard',
                'inventory',
                'sales',
                'pos',
                'forecast',
                'spoilage',
                'analytics',
                'reports',
                'decision-support',
                'notifications',
                'settings',
            ],
            'manager' => [
                'pos',
                'sales',
                'inventory',
                'reports',
                'forecast',
                'spoilage',
                'analytics',
            ],
            'cashier' => [
                'pos',
                'settings',
                'notifications',
            ],
        ];

        return in_array($feature, $permissions[$this->role] ?? []);
    }
}
