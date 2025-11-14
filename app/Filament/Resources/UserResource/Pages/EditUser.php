<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $userType = $user->user_type;

        // Assign role based on user_type if Spatie Permission is available
        if (class_exists('Spatie\Permission\Models\Role')) {
            $roleClass = 'Spatie\Permission\Models\Role';

            // Remove all existing roles
            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([]);
            }

            // Assign role based on user_type
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

