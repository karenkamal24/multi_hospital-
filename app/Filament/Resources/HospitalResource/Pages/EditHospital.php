<?php

namespace App\Filament\Resources\HospitalResource\Pages;

use App\Filament\Resources\HospitalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditHospital extends EditRecord
{
    protected static string $resource = HospitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // إذا كان المستخدم من نوع hospital، لا يمكنه تعديل user_id
        if (Auth::check() && Auth::user()?->user_type === 'hospital') {
            // التأكد من أن user_id مرتبط بالمستخدم الحالي
            $data['user_id'] = Auth::id();
        }

        return $data;
    }
}

