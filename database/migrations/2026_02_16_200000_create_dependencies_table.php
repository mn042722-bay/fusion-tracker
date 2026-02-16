<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_component_id')->constrained('components')->cascadeOnDelete();
            $table->foreignId('to_component_id')->constrained('components')->cascadeOnDelete();
            $table->string('relation');
            $table->enum('strength', ['high', 'medium', 'low'])->default('medium');
            $table->timestamps();

            $table->unique(['from_component_id', 'to_component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencies');
    }
};