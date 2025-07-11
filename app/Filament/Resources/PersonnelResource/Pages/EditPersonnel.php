<?php

namespace App\Filament\Resources\PersonnelResource\Pages;

use App\Filament\Resources\PersonnelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPersonnel extends EditRecord
{
    protected static string $resource = PersonnelResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];

        // Add certificate upload action if personnel has completed trainings
        $personnel = $this->record;
        $completedTrainings = $personnel->trainings()->wherePivot('status', 'completed')->get();
        if ($completedTrainings->count() > 0) {
            $actions[] = Actions\Action::make('upload_certificate')
                ->label('Upload Certificate')
                ->form([
                    \Filament\Forms\Components\Select::make('training_id')
                        ->label('Training')
                        ->options($completedTrainings->pluck('title', 'id'))
                        ->required(),
                    \Filament\Forms\Components\FileUpload::make('file_path')
                        ->label('Certificate File')
                        ->directory('certificates')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('issued_at')
                        ->label('Issued At')
                        ->required(),
                ])
                ->action(function (array $data) use ($personnel) {
                    \App\Models\Certificate::create([
                        'personnel_id' => $personnel->id,
                        'training_id' => $data['training_id'],
                        'file_path' => $data['file_path'],
                        'issued_at' => $data['issued_at'],
                    ]);
                })
                ->successNotificationTitle('Certificate uploaded successfully!');
        }
        return $actions;
    }
}