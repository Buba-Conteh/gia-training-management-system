<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'department_id',
        'trainer_id',
        'thumbnail',
        'start_date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    // public function trainingMaterials(): HasMany
    // {
    //     return $this->hasMany(TrainingMaterial::class);
    // }

    public function personnel(): BelongsToMany
    {
        return $this->belongsToMany(Personnel::class, 'personnel_training')
            ->withPivot(['assigned_by', 'assigned_at', 'status', 'started_at', 'completed_at'])
            ->withTimestamps();
    }

    public function trainingMaterials(): HasMany
    {
        return $this->hasMany(TrainingMaterial::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }
}