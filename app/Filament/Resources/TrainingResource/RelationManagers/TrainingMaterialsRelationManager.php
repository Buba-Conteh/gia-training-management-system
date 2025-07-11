<?php

namespace App\Filament\Resources\TrainingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TrainingMaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'trainingMaterials';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Forms\Components\FileUpload::make('file_path')
                    ->label('Upload File')
                    ->directory('training-materials')
                    ->acceptedFileTypes(['application/pdf', 'video/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])
                    ->maxSize(10240) // 10MB
                    ->openable()
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Material'),
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
}