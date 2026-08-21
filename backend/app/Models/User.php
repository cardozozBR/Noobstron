<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\Role;
use App\Traits\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, BelongsToTenant, MustVerifyEmailTrait;

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function hasPermission(Permission $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissions()
            ->where('name', $permission->value)
            ->exists();
    }

    public function permissions(): BelongsToMany
{
    return $this->belongsToMany(\App\Models\Permission::class);
}

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function assignedLeads()
    {
        return $this->hasMany(
            Lead::class,
            'responsible_user_id'
        );
    }
    public function assignedCustomers()
    {
        return $this->hasMany(
            Customer::class,
            'responsible_user_id'
        );
    }

public function assignedOpportunities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Opportunity::class,
            'responsible_user_id'
        );
    }

    public function assignedActivities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Activity::class,
            'responsible_user_id'
        );
    }
}
