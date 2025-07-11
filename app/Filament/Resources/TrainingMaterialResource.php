<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingMaterialResource\Pages;
use App\Models\TrainingMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrainingMaterialResource extends Resource
{
    protected static ?string $model = TrainingMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Training Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Material Information')
                    ->schema([
                        Forms\Components\Select::make('training_id')
                            ->relationship('training', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Training'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'pdf' => 'PDF Document',
                                'video' => 'Video',
                                'document' => 'Document',
                                'presentation' => 'Presentation',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->reactive(),
                    ])->columns(2),

                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Upload File')
                            ->directory('training-materials')
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'video/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])
                            ->maxSize(10240) // 10MB
                            ->visible(fn ($get) => $get('type') !== 'video' || !$get('external_url')),
                        Forms\Components\TextInput::make('external_url')
                            ->label('External URL (for videos)')
                            ->url()
                            ->visible(fn ($get) => $get('type') === 'video'),
                        Forms\Components\TextInput::make('file_name')
                            ->label('File Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('file_size')
                            ->label('File Size (bytes)')
                            ->numeric(),
                        Forms\Components\TextInput::make('mime_type')
                            ->label('MIME Type')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->label('Display Order'),
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required Material')
                            ->default(true),
                        Forms\Components\Select::make('uploaded_by')
                            ->relationship('uploadedBy', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->label('Uploaded By'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('training.title')
                    ->searchable()
                    ->sortable()
                    ->label('Training'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pdf' => 'danger',
                        'video' => 'warning',
                        'document' => 'info',
                        'presentation' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable()
                    ->sortable()
                    ->label('File Name'),
                Tables\Columns\TextColumn::make('file_size_formatted')
                    ->label('File Size'),
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label('Order'),
                Tables\Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->searchable()
                    ->sortable()
                    ->label('Uploaded By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training')
                    ->relationship('training', 'title'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'pdf' => 'PDF',
                        'video' => 'Video',
                        'document' => 'Document',
                        'presentation' => 'Presentation',
                        'other' => 'Other',
                    ]),
                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Required Materials Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingMaterials::route('/'),
            'create' => Pages\CreateTrainingMaterial::route('/create'),
            'view' => Pages\ViewTrainingMaterial::route('/{record}'),
            'edit' => Pages\EditTrainingMaterial::route('/{record}/edit'),
        ];
    }
}