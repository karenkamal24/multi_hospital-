<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الأدوار إذا كان Spatie Permission مثبت
        if (class_exists('Spatie\Permission\Models\Role')) {
            $roleClass = 'Spatie\Permission\Models\Role';
            $roleClass::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            $roleClass::firstOrCreate(['name' => 'hospital', 'guard_name' => 'web']);
            $roleClass::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
            $roleClass::firstOrCreate(['name' => 'donner', 'guard_name' => 'web']);
        }

        // 1. إنشاء Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password123'),
                'user_type' => 'super_admin',
                'phone' => '0501234567',
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($superAdmin, 'assignRole')) {
            $superAdmin->assignRole('super_admin');
        }
        $this->command->info('✅ تم إنشاء Super Admin: admin@admin.com / password123');

        // 2. إنشاء مستخدمين Hospital
        $hospitalUser1 = User::firstOrCreate(
            ['email' => 'hospital1@hospital.com'],
            [
                'name' => 'مستشفى النور',
                'password' => Hash::make('password123'),
                'user_type' => 'hospital',
                'phone' => '0501111111',
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($hospitalUser1, 'assignRole')) {
            $hospitalUser1->assignRole('hospital');
        }
        $this->command->info('✅ تم إنشاء مستخدم Hospital 1: hospital1@hospital.com / password123');

        $hospitalUser2 = User::firstOrCreate(
            ['email' => 'hospital2@hospital.com'],
            [
                'name' => 'مستشفى الأمل',
                'password' => Hash::make('password123'),
                'user_type' => 'hospital',
                'phone' => '0502222222',
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($hospitalUser2, 'assignRole')) {
            $hospitalUser2->assignRole('hospital');
        }
        $this->command->info('✅ تم إنشاء مستخدم Hospital 2: hospital2@hospital.com / password123');

        // 3. إنشاء المستشفيات
        $hospital1 = Hospital::firstOrCreate(
            ['user_id' => $hospitalUser1->id],
            [
                'name' => 'مستشفى النور التخصصي',
                'address' => 'شارع الملك فهد، الرياض، السعودية',
                'location' => 'الرياض، السعودية',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ]
        );
        $this->command->info('✅ تم إنشاء مستشفى النور التخصصي');

        $hospital2 = Hospital::firstOrCreate(
            ['user_id' => $hospitalUser2->id],
            [
                'name' => 'مستشفى الأمل',
                'address' => 'شارع العليا، الرياض، السعودية',
                'location' => 'الرياض، السعودية',
                'latitude' => 24.7236,
                'longitude' => 46.6853,
            ]
        );
        $this->command->info('✅ تم إنشاء مستشفى الأمل');

        // 4. إنشاء مرضى (3)
        $patients = [
            [
                'email' => 'patient1@test.com',
                'name' => 'أحمد محمد',
                'phone' => '0503333333',
                'blood' => 'O+',
                'gender' => 'male',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ],
            [
                'email' => 'patient2@test.com',
                'name' => 'فاطمة أحمد',
                'phone' => '0503333334',
                'blood' => 'A+',
                'gender' => 'female',
                'latitude' => 24.7200,
                'longitude' => 46.6800,
            ],
            [
                'email' => 'patient3@test.com',
                'name' => 'خالد سعيد',
                'phone' => '0503333335',
                'blood' => 'B+',
                'gender' => 'male',
                'latitude' => 24.7150,
                'longitude' => 46.6700,
            ],
        ];

        foreach ($patients as $index => $patientData) {
            $patient = User::firstOrCreate(
                ['email' => $patientData['email']],
                [
                    'name' => $patientData['name'],
                    'password' => Hash::make('password123'),
                    'user_type' => 'patient',
                    'phone' => $patientData['phone'],
                    'blood' => $patientData['blood'],
                    'gender' => $patientData['gender'],
                    'latitude' => $patientData['latitude'],
                    'longitude' => $patientData['longitude'],
                    'email_verified_at' => now(),
                ]
            );
            if (method_exists($patient, 'assignRole')) {
                $patient->assignRole('patient');
            }
            $this->command->info("✅ تم إنشاء مريض " . ($index + 1) . ": {$patientData['email']} / password123");
        }

        // 5. إنشاء متبرعين (5)
        $donors = [
            [
                'email' => 'donor1@test.com',
                'name' => 'محمد علي',
                'phone' => '0504444444',
                'blood' => 'O+',
                'gender' => 'male',
                'latitude' => 24.7236,
                'longitude' => 46.6853,
            ],
            [
                'email' => 'donor2@test.com',
                'name' => 'سارة حسن',
                'phone' => '0504444445',
                'blood' => 'O+',
                'gender' => 'female',
                'latitude' => 24.7100,
                'longitude' => 46.6600,
            ],
            [
                'email' => 'donor3@test.com',
                'name' => 'علي محمود',
                'phone' => '0504444446',
                'blood' => 'A+',
                'gender' => 'male',
                'latitude' => 24.7250,
                'longitude' => 46.6900,
            ],
            [
                'email' => 'donor4@test.com',
                'name' => 'نورا إبراهيم',
                'phone' => '0504444447',
                'blood' => 'B+',
                'gender' => 'female',
                'latitude' => 24.7180,
                'longitude' => 46.6750,
            ],
            [
                'email' => 'donor5@test.com',
                'name' => 'يوسف عبدالله',
                'phone' => '0504444448',
                'blood' => 'AB+',
                'gender' => 'male',
                'latitude' => 24.7000,
                'longitude' => 46.6500,
            ],
        ];

        foreach ($donors as $index => $donorData) {
            $donor = User::firstOrCreate(
                ['email' => $donorData['email']],
                [
                    'name' => $donorData['name'],
                    'password' => Hash::make('password123'),
                    'user_type' => 'donner',
                    'phone' => $donorData['phone'],
                    'blood' => $donorData['blood'],
                    'gender' => $donorData['gender'],
                    'latitude' => $donorData['latitude'],
                    'longitude' => $donorData['longitude'],
                    'email_verified_at' => now(),
                ]
            );
            if (method_exists($donor, 'assignRole')) {
                $donor->assignRole('donner');
            }
            $this->command->info("✅ تم إنشاء متبرع " . ($index + 1) . ": {$donorData['email']} / password123");
        }

        // 7. إنشاء إعدادات
        Setting::firstOrCreate(
            ['key' => 'sos_radius_km'],
            [
                'value' => '10',
                'description' => 'مسافة البحث عن المتبرعين بالكيلومتر',
            ]
        );
        $this->command->info('✅ تم إنشاء إعداد sos_radius_km = 10 كم');

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('🎉 تم إنشاء جميع البيانات بنجاح!');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('📋 بيانات الدخول:');
        $this->command->newLine();
        $this->command->info('Super Admin:');
        $this->command->info('  Email: admin@hospital.com');
        $this->command->info('  Password: password123');
        $this->command->newLine();
        $this->command->info('Hospital 1:');
        $this->command->info('  Email: hospital1@hospital.com');
        $this->command->info('  Password: password123');
        $this->command->newLine();
        $this->command->info('Hospital 2:');
        $this->command->info('  Email: hospital2@hospital.com');
        $this->command->info('  Password: password123');
        $this->command->newLine();
        $this->command->info('Patients (3):');
        $this->command->info('  patient1@test.com / password123 (أحمد محمد - O+)');
        $this->command->info('  patient2@test.com / password123 (فاطمة أحمد - A+)');
        $this->command->info('  patient3@test.com / password123 (خالد سعيد - B+)');
        $this->command->newLine();
        $this->command->info('Donors (5):');
        $this->command->info('  donor1@test.com / password123 (محمد علي - O+)');
        $this->command->info('  donor2@test.com / password123 (سارة حسن - O+)');
        $this->command->info('  donor3@test.com / password123 (علي محمود - A+)');
        $this->command->info('  donor4@test.com / password123 (نورا إبراهيم - B+)');
        $this->command->info('  donor5@test.com / password123 (يوسف عبدالله - AB+)');
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════');
    }
}
