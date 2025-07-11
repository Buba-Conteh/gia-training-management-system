<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings');
            $table->string('file_path');
            $table->integer('version');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_files');
    }
};