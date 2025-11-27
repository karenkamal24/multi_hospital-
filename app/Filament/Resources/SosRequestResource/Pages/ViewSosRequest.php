<?php

namespace App\Filament\Resources\SosRequestResource\Pages;

use App\Filament\Resources\SosRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSosRequest extends ViewRecord
{
    protected static string $resource = SosRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}



