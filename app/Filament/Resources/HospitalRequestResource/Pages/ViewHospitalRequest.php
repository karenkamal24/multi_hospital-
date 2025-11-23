<?php

namespace App\Filament\Resources\HospitalRequestResource\Pages;

use App\Filament\Resources\HospitalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHospitalRequest extends ViewRecord
{
    protected static string $resource = HospitalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}


