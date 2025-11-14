<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    // Add HasRoles trait if Spatie Permission is installed
    // After running: composer install, uncomment the line below
    // use Spatie\Permission\Traits\HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'user_type',
        'blood',
        'password',
        'fcm_token',
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
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Check user_type directly
        // After installing spatie/laravel-permission, this will work with roles too
        return in_array($this->user_type, ['super_admin', 'hospital']);
    }

    /**
     * Check if user has a role.
     * Works with user_type now, will work with Spatie Permission after installation.
     * After installing spatie/laravel-permission and adding HasRoles trait,
     * this method will be overridden by the trait.
     */
    public function hasRole($roles): bool
    {
        // Normalize roles to array
        if (is_string($roles)) {
            $roles = [$roles];
        } elseif ($roles instanceof \Illuminate\Database\Eloquent\Model) {
            $roles = [$roles->name];
        }

        // Check user_type
        $userType = $this->user_type ?? null;

        foreach ($roles as $role) {
            // Handle role name or role object
            $roleName = is_string($role) ? $role : ($role->name ?? null);

            if ($roleName && $roleName === $userType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign role to user.
     * Works with user_type now, will work with Spatie Permission after installation.
     * After installing spatie/laravel-permission and adding HasRoles trait,
     * this method will be overridden by the trait.
     */
    public function assignRole($role): self
    {
        // Get role name - handle string or object
        $roleName = null;

        if (is_string($role)) {
            $roleName = $role;
        } elseif (is_object($role)) {
            // Try multiple ways to get the role name
            try {
                // Try direct property access (works for Eloquent models)
                if (isset($role->name)) {
                    $roleName = $role->name;
                } elseif (method_exists($role, 'getName')) {
                    $roleName = $role->getName();
                } elseif (method_exists($role, '__get')) {
                    $roleName = $role->name;
                } elseif (method_exists($role, 'getAttribute')) {
                    $roleName = $role->getAttribute('name');
                }
            } catch (\Exception $e) {
                // If all methods fail, try to cast to string or use get_class
                // For now, just skip
            }
        }

        if ($roleName) {
            // Map role name to user_type
            $roleToUserType = [
                'super_admin' => 'super_admin',
                'hospital' => 'hospital',
                'patient' => 'patient',
                'donner' => 'donner',
            ];

            if (isset($roleToUserType[$roleName])) {
                $this->user_type = $roleToUserType[$roleName];
                $this->save();
            }
        }

        return $this;
    }

    /**
     * Sync roles for user.
     * Works with user_type now, will work with Spatie Permission after installation.
     * After installing spatie/laravel-permission and adding HasRoles trait,
     * this method will be overridden by the trait.
     */
    public function syncRoles($roles): self
    {
        // If empty array, clear user_type or set to default
        if (empty($roles)) {
            // Optionally set to a default user_type or leave as is
            // $this->user_type = null;
            return $this;
        }

        // Get first role and assign it
        $firstRole = is_array($roles) ? ($roles[0] ?? null) : $roles;

        if ($firstRole) {
            $this->assignRole($firstRole);
        }

        return $this;
    }

    /**
     * Get the hospital associated with the user.
     */
    public function hospital(): HasOne
    {
        return $this->hasOne(Hospital::class);
    }
}
