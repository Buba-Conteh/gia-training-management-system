<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingResource\Pages;
use App\Filament\Resources\TrainingResource\RelationManagers;
use App\Models\Training;
use App\Models\Personnel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Support\Facades\DB;

class TrainingResource extends Resource
{
    protected static ?string $model = Training::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Training Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Training Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('thumbnail')
                            ->image()
                            ->directory('training-thumbnails')
                            ->label('Thumbnail')
                            ->imageEditor()
                            ->maxSize(2048),
                        Forms\Components\Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('trainer_id')
                            ->relationship('trainer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Stack::make([
                    // Columns
                    Tables\Columns\ImageColumn::make('thumbnail')
                        ->label('Thumbnail')
                        ->circular(),
                    Tables\Columns\TextColumn::make('title')
                        ->searchable()
                        ->sortable(),
                    // Tables\Columns\TextColumn::make('description')
                    //     ->limit(50)
                    //     ->searchable(),
                    Tables\Columns\TextColumn::make('department.name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('trainer.name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('training_materials_count')
                        ->counts('trainingMaterials')
                        ->label('Files'),
                    Tables\Columns\TextColumn::make('personnel_count')
                        ->counts('personnel')
                        ->label('No# Personnel'),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                // ]),
            ])
            // ->contentGrid([
            //     'md' => 2,
            //     'xl' => 3,
            // ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name'),
                Tables\Filters\SelectFilter::make('trainer')
                    ->relationship('trainer', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign_personnel')
                    ->label('Personnel')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->url(fn (Training $record): string => route('filament.admin.resources.trainings.edit', ['record' => $record, 'activeTab' => 'personnel'])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign_personnel')
                        ->label('Assign Personnel to Selected Trainings')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->form([
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
                                ->options(\App\Models\Department::pluck('name', 'id'))
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
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $training) {
                                if ($data['assignment_type'] === 'department') {
                                    $personnelIds = Personnel::where('department_id', $data['department_id'])
                                        ->where('status', 'active')
                                        ->pluck('id');
                                } else {
                                    $personnelIds = collect($data['personnel_ids']);
                                }

                                foreach ($personnelIds as $personnelId) {
                                    $training->personnel()->syncWithoutDetaching([
                                        $personnelId => [
                                            'assigned_by' => $data['assigned_by'],
                                            'assigned_at' => $data['assigned_at'],
                                            'status' => 'pending',
                                        ]
                                    ]);
                                }
                            }
                        })
                        ->successNotificationTitle('Personnel assigned to selected trainings successfully!'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    //         ViewEntry::make('status')
    // ->view('filament.infolists.entries.status-switcher')
    // ->registerActions([
    //     Action::make('createStatus')
    //         ->form([
    //             TextInput::make('name')
    //                 ->required(),
    //         ])
    //         ->icon('heroicon-m-plus')
    //         ->action(function (array $data, Personnel $record) {
    //             $record->status()->create($data);
    //         }),
    //     ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PersonnelRelationManager::class,
            RelationManagers\TrainingMaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainings::route('/'),
            'create' => Pages\CreateTraining::route('/create'),
            'view' => Pages\ViewTraining::route('/{record}'),
            'edit' => Pages\EditTraining::route('/{record}/edit'),
        ];
    }
}