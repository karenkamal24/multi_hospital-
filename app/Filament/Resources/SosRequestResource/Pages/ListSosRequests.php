<?php

namespace App\Filament\Resources\SosRequestResource\Pages;

use App\Filament\Resources\SosRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSosRequests extends ListRecords
{
    protected static string $resource = SosRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}



