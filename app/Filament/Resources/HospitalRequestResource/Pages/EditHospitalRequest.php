<?php

namespace App\Filament\Resources\HospitalRequestResource\Pages;

use App\Filament\Resources\HospitalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHospitalRequest extends EditRecord
{
    protected static string $resource = HospitalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}


