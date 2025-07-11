<?php

namespace App\Filament\Resources\TrainingMaterialResource\Pages;

use App\Filament\Resources\TrainingMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrainingMaterial extends ViewRecord
{
    protected static string $resource = TrainingMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}