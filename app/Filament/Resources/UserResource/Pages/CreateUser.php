<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;
        $userType = $user->user_type;

        // Assign role based on user_type if Spatie Permission is available
        if (class_exists('Spatie\Permission\Models\Role')) {
            $roleClass = 'Spatie\Permission\Models\Role';
            
            $roleName = match($userType) {
                'super_admin' => 'super_admin',
                'hospital' => 'hospital',
                'patient' => 'patient',
                'donner' => 'donner',
                default => null,
            };

            if ($roleName && method_exists($user, 'assignRole')) {
                $role = $roleClass::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web'
                ]);
                $user->assignRole($role);
            }
        }
    }
}

