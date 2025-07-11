<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }
}