<?php

namespace App\Filament\Resources\EMIResource\Pages;

use App\Filament\Resources\EMIResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEMIS extends ListRecords
{
    protected static string $resource = EMIResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
