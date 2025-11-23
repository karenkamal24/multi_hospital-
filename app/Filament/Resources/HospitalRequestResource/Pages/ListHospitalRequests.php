<?php

namespace App\Filament\Resources\HospitalRequestResource\Pages;

use App\Filament\Resources\HospitalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHospitalRequests extends ListRecords
{
    protected static string $resource = HospitalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


