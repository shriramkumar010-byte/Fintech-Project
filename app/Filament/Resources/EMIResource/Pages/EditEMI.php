<?php

namespace App\Filament\Resources\EMIResource\Pages;

use App\Filament\Resources\EMIResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEMI extends EditRecord
{
    protected static string $resource = EMIResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
