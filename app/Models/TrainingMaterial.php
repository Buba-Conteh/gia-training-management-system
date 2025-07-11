<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'title',
        'description',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'external_url',
        'order',
        'is_required',
        'uploaded_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // public function assignmentProgress(): HasMany
    // {
    //     return $this->hasMany(TrainingAssignmentProgress::class);
    // }

    public function getFileUrlAttribute(): ?string
    {
        if ($this->external_url) {
            return $this->external_url;
        }

        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }

        return null;
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = (int) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'pdf' => 'heroicon-o-document-text',
            'video' => 'heroicon-o-video-camera',
            'document' => 'heroicon-o-document',
            'presentation' => 'heroicon-o-presentation-chart-line',
            default => 'heroicon-o-document',
        };
    }
}