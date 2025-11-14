<?php

namespace App\Filament\Resources\HospitalResource\Pages;

use App\Filament\Resources\HospitalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHospital extends CreateRecord
{
    protected static string $resource = HospitalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // إذا كان المستخدم من نوع hospital، ربط المستشفى بحسابه
        if (Auth::check() && Auth::user()->user_type === 'hospital') {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }
}

