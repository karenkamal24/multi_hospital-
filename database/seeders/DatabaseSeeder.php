<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if Spatie Permission is available
        if (class_exists('Spatie\Permission\Models\Role')) {
            // Create roles
            $roleClass = 'Spatie\Permission\Models\Role';
            $superAdminRole = $roleClass::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            $hospitalRole = $roleClass::firstOrCreate(['name' => 'hospital', 'guard_name' => 'web']);
            $patientRole = $roleClass::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
            $donnerRole = $roleClass::firstOrCreate(['name' => 'donner', 'guard_name' => 'web']);

            // Create Super Admin user
            $superAdmin = User::firstOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password123'),
                    'user_type' => 'super_admin',
                    'email_verified_at' => now(),
                ]
            );

            // Assign super_admin role to super admin user
            if (!$superAdmin->hasRole('super_admin')) {
                $superAdmin->assignRole('super_admin');
            }
        } else {
            // Fallback: Create Super Admin user without roles
            User::firstOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password123'),
                    'user_type' => 'super_admin',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
