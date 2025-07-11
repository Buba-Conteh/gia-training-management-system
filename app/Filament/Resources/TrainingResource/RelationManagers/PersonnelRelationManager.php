<?php

namespace App\Filament\Resources\TrainingResource\RelationManagers;

use App\Models\Personnel;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class PersonnelRelationManager extends RelationManager
{
    protected static string $relationship = 'personnel';

    protected static ?string $recordTitleAttribute = 'first_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('assignment_type')
                    ->label('Assignment Type')
                    ->options([
                        'individual' => 'Individual Personnel',
                        'department' => 'Entire Department',
                    ])
                    ->required()
                    ->reactive()
                    ->default('individual'),
                Forms\Components\Select::make('department_id')
                    ->label('Department')
                    ->options(Department::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('assignment_type') === 'department')
                    ->required(fn ($get) => $get('assignment_type') === 'department'),
                Forms\Components\Select::make('personnel_ids')
                    ->label('Personnel')
                    ->multiple()
                    ->options(fn ($get) => Personnel::query()
                        ->when($get('department_id'), fn ($query, $dept) => $query->where('department_id', $dept))
                        ->where('status', 'active')
                        ->pluck('first_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('assignment_type') === 'individual')
                    ->required(fn ($get) => $get('assignment_type') === 'individual'),
                Forms\Components\Select::make('assigned_by')
                    ->label('Assigned By')
                    ->options(\App\Models\User::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->default(auth()->id()),
                Forms\Components\DateTimePicker::make('assigned_at')
                    ->label('Assigned At')
                    ->required()
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\ImageColumn::make('profile_image')
                    ->label('Profile')
                    ->circular(),
                Tables\Columns\TextColumn::make('employee_id')
                    ->searchable()
                    ->sortable()
                    ->label('Employee ID'),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->searchable()
                    ->sortable()
                    ->label('Department'),
                Tables\Columns\TextColumn::make('pivot.status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->label('Status'),
                // Tables\Columns\TextColumn::make('pivot.assigned_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->label('Assigned At'),
                // Tables\Columns\TextColumn::make('pivot.started_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->label('Started At'),
                Tables\Columns\TextColumn::make('pivot.completed_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Completed At'),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('status')
                //     ->options([
                //         'pending' => 'Pending',
                //         'in_progress' => 'In Progress',
                //         'completed' => 'Completed',
                //     ])
                //     ->query(fn ($query, array $data) =>
                //         $query->wherePivot('status', $data['values'])
                //     ),
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Assign Personnel')
                    ->mutateFormDataUsing(function (array $data): array {
                        $training = $this->getOwnerRecord();
                        if ($training->start_date && now()->greaterThanOrEqualTo($training->start_date)) {
                            Notification::make()
                                ->title('Cannot assign personnel after training has started')
                                ->danger()
                                ->send();
                            throw ValidationException::withMessages([
                                'assignment_type' => ['Cannot assign personnel after training has started.'],
                            ]);
                        }
                        // Handle the assignment logic
                        if ($data['assignment_type'] === 'department') {
                            $personnelIds = \App\Models\Personnel::where('department_id', $data['department_id'])
                                ->where('status', 'active')
                                ->pluck('id');
                        } else {
                            $personnelIds = collect($data['personnel_ids']);
                        }
                        foreach ($personnelIds as $personnelId) {
                            $this->getOwnerRecord()->personnel()->attach($personnelId, [
                                'assigned_by' => $data['assigned_by'],
                                'assigned_at' => $data['assigned_at'],
                                'status' => 'pending',
                            ]);
                        }
                        return $data;
                    })
                    ->successNotificationTitle('Personnel assigned successfully!'),
            ])
            ->actions([
                Tables\Actions\Action::make('update')
                    ->label('Update')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $this->getOwnerRecord()->personnel()->updateExistingPivot($record->id, [
                            'status' => $data['status'],
                            'started_at' => $data['status'] === 'in_progress' ? now() : null,
                            'completed_at' => $data['status'] === 'completed' ? now() : null,
                        ]);
                    }),
                Tables\Actions\DetachAction::make()
                    ->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove'),
                ]),
            ]);
    }
}