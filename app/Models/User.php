<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function laboratoryStaff(): HasOne
    {
        return $this->hasOne(LaboratoryStaff::class);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function hasRole(string $code): bool
    {
        return $this->roles->contains('code', $code);
    }

    /**
     * Permission-based check — never use role-name hardcoding for approval gates.
     */
    public function hasPermission(string $code): bool
    {
        if (! $this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        } elseif ($this->roles->isNotEmpty() && ! $this->roles->first()->relationLoaded('permissions')) {
            $this->load('roles.permissions');
        }

        foreach ($this->roles as $role) {
            if ($role->permissions->contains('code', $code)) {
                return true;
            }
        }

        return false;
    }

    public function permissionCodes(): array
    {
        if (! $this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        }

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('code'))
            ->unique()
            ->values()
            ->all();
    }
}
